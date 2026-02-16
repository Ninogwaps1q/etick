<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTick Setup Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .check-item { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .check-pass { background-color: #d1e7dd; border-left: 4px solid #198754; }
        .check-fail { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .check-warning { background-color: #fff3cd; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">eTick Setup Checker</h2>
                    </div>
                    <div class="card-body">
                        <p class="lead">This tool checks if your environment is properly configured for eTick.</p>
                        <hr>

                        <h4>PHP Configuration</h4>
                        <?php
                        $phpVersion = phpversion();
                        $requiredVersion = '7.4.0';
                        $phpOk = version_compare($phpVersion, $requiredVersion, '>=');
                        ?>
                        <div class="check-item <?php echo $phpOk ? 'check-pass' : 'check-fail'; ?>">
                            <strong>PHP Version:</strong> <?php echo $phpVersion; ?>
                            <?php if ($phpOk): ?>
                                &#10003; (Required: <?php echo $requiredVersion; ?>+)
                            <?php else: ?>
                                &#10007; (Required: <?php echo $requiredVersion; ?>+)
                            <?php endif; ?>
                        </div>

                        <?php
                        $requiredExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring'];
                        $optionalExtensions = ['gd'];
                        $allRequiredExtensionsLoaded = true;

                        foreach ($requiredExtensions as $ext):
                            $loaded = extension_loaded($ext);
                            $allRequiredExtensionsLoaded = $allRequiredExtensionsLoaded && $loaded;
                        ?>
                        <div class="check-item <?php echo $loaded ? 'check-pass' : 'check-fail'; ?>">
                            <strong>PHP Extension (<?php echo $ext; ?>):</strong>
                            <?php echo $loaded ? '&#10003; Loaded' : '&#10007; Not Loaded'; ?>
                        </div>
                        <?php endforeach; ?>

                        <?php foreach ($optionalExtensions as $ext):
                            $loaded = extension_loaded($ext);
                        ?>
                        <div class="check-item <?php echo $loaded ? 'check-pass' : 'check-warning'; ?>">
                            <strong>PHP Extension (<?php echo $ext; ?>):</strong>
                            <?php echo $loaded ? '&#10003; Loaded' : 'Optional (recommended for image processing)'; ?>
                        </div>
                        <?php endforeach; ?>

                        <hr>
                        <h4>File Permissions</h4>
                        <?php
                        $uploadDir = __DIR__ . '/uploads/events/';
                        $uploadWritable = is_writable($uploadDir);
                        ?>
                        <div class="check-item <?php echo $uploadWritable ? 'check-pass' : 'check-fail'; ?>">
                            <strong>Upload Directory (uploads/events/):</strong>
                            <?php echo $uploadWritable ? '&#10003; Writable' : '&#10007; Not Writable'; ?>
                            <?php if (!$uploadWritable): ?>
                                <br><small>Run: chmod -R 755 uploads/</small>
                            <?php endif; ?>
                        </div>

                        <hr>
                        <h4>Database Connection</h4>
                        <?php
                        require_once __DIR__ . '/config/database.php';
                        $dbConnected = false;
                        $dbError = '';
                        $allTablesExist = false;
                        $conn = null;

                        $db = new Database();
                        $conn = $db->connect();
                        if ($conn) {
                            $dbConnected = true;
                            $allTablesExist = true;
                        } else {
                            $dbError = $db->getLastError();
                        }
                        ?>
                        <div class="check-item <?php echo $dbConnected ? 'check-pass' : 'check-fail'; ?>">
                            <strong>Database Connection:</strong>
                            <?php echo $dbConnected ? '&#10003; Connected' : '&#10007; Failed'; ?>
                            <?php if (!$dbConnected): ?>
                                <br><small class="text-danger"><?php echo htmlspecialchars($dbError); ?></small>
                                <br><small>Check your database credentials in config/database.php and ensure MySQL is running.</small>
                            <?php endif; ?>
                        </div>

                        <?php if ($dbConnected): ?>
                            <?php
                            $tables = ['users', 'events', 'bookings'];
                            foreach ($tables as $table):
                                $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                                $tableExists = $stmt->rowCount() > 0;
                                $allTablesExist = $allTablesExist && $tableExists;
                            ?>
                            <div class="check-item <?php echo $tableExists ? 'check-pass' : 'check-fail'; ?>">
                                <strong>Table (<?php echo $table; ?>):</strong>
                                <?php echo $tableExists ? '&#10003; Exists' : '&#10007; Not Found'; ?>
                                <?php if (!$tableExists): ?>
                                    <br><small>Import database/etick.sql in phpMyAdmin</small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <hr>
                        <h4>Server Configuration</h4>
                        <?php
                        $uploadMaxFilesize = ini_get('upload_max_filesize');
                        $postMaxSize = ini_get('post_max_size');
                        ?>
                        <div class="check-item <?php echo (intval($uploadMaxFilesize) >= 5) ? 'check-pass' : 'check-warning'; ?>">
                            <strong>Upload Max Filesize:</strong> <?php echo $uploadMaxFilesize; ?>
                            <?php if (intval($uploadMaxFilesize) < 5): ?>
                                <br><small>Recommended: 5M or higher</small>
                            <?php endif; ?>
                        </div>

                        <div class="check-item <?php echo (intval($postMaxSize) >= 5) ? 'check-pass' : 'check-warning'; ?>">
                            <strong>Post Max Size:</strong> <?php echo $postMaxSize; ?>
                            <?php if (intval($postMaxSize) < 5): ?>
                                <br><small>Recommended: 5M or higher</small>
                            <?php endif; ?>
                        </div>

                        <hr>
                        <div class="alert alert-info">
                            <strong>Next Steps:</strong>
                            <?php if ($phpOk && $allRequiredExtensionsLoaded && $dbConnected && $allTablesExist && $uploadWritable): ?>
                                <p class="mb-0">&#10003; All checks passed! Your system is ready.</p>
                                <p class="mt-2 mb-0">
                                    <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                                    <a href="login.php" class="btn btn-success ms-2">Login</a>
                                </p>
                            <?php else: ?>
                                <p class="mb-0">Please fix the issues marked above and refresh this page.</p>
                            <?php endif; ?>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <strong>Default Admin Login:</strong><br>
                            Email: admin@etick.com<br>
                            Password: admin123
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

