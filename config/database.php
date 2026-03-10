<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'etick');

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $port = DB_PORT;
    private $conn;
    private $lastError = '';
    private static $schemaChecked = false;

    public function connect() {
        $this->conn = null;
        $this->lastError = '';

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=utf8mb4',
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->runSchemaMigrations();
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->conn = null;
        }

        return $this->conn;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function runSchemaMigrations() {
        if (self::$schemaChecked || !$this->conn) {
            return;
        }

        // Skip migrations for fresh setup until tables are created.
        if (!$this->tableExists('users') || !$this->tableExists('bookings')) {
            self::$schemaChecked = true;
            return;
        }

        // Roles: move legacy "user" to "customer" and ensure Organizer support.
        if ($this->columnExists('users', 'role')) {
            $roleColumnType = strtolower((string) $this->getColumnType('users', 'role'));
            $hasCustomerRole = strpos($roleColumnType, "'customer'") !== false;
            $hasOrganizerRole = strpos($roleColumnType, "'organizer'") !== false;
            $hasLegacyUserRole = strpos($roleColumnType, "'user'") !== false;

            if (!$hasCustomerRole || !$hasOrganizerRole) {
                $this->conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'customer', 'organizer', 'admin') NOT NULL DEFAULT 'customer'");
                $hasLegacyUserRole = true;
            }

            $this->conn->exec("UPDATE users SET role = 'customer' WHERE role = 'user'");

            if ($hasLegacyUserRole) {
                $this->conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'organizer', 'admin') NOT NULL DEFAULT 'customer'");
            }
        }

        // Ticket details for per-ticket type and pricing.
        if (!$this->columnExists('bookings', 'ticket_type')) {
            $this->conn->exec("ALTER TABLE bookings ADD COLUMN ticket_type VARCHAR(50) NOT NULL DEFAULT 'Regular' AFTER event_id");
        }

        if (!$this->columnExists('bookings', 'unit_price')) {
            $this->conn->exec("ALTER TABLE bookings ADD COLUMN unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER ticket_type");
        }

        $this->conn->exec("
            UPDATE bookings
            SET unit_price = CASE
                WHEN ticket_quantity > 0 THEN ROUND(total_amount / ticket_quantity, 2)
                ELSE total_amount
            END
            WHERE unit_price = 0
        ");

        self::$schemaChecked = true;
    }

    private function tableExists($tableName) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_name = ?
        ");
        $stmt->execute([$this->dbname, $tableName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists($tableName, $columnName) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
              AND column_name = ?
        ");
        $stmt->execute([$this->dbname, $tableName, $columnName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function getColumnType($tableName, $columnName) {
        $stmt = $this->conn->prepare("
            SELECT COLUMN_TYPE
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ");
        $stmt->execute([$this->dbname, $tableName, $columnName]);
        return $stmt->fetchColumn();
    }
}
