<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_base_url() {
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $projectRoot = realpath(__DIR__ . '/..');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot && $documentRoot) {
        $projectRootNorm = str_replace('\\', '/', $projectRoot);
        $documentRootNorm = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if (stripos($projectRootNorm, $documentRootNorm) === 0) {
            $relativePath = trim(substr($projectRootNorm, strlen($documentRootNorm)), '/');
            $baseUrl = $relativePath === '' ? '/' : '/' . $relativePath . '/';
            return $baseUrl;
        }
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName !== '') {
        $parts = explode('/', trim($scriptName, '/'));
        if (!empty($parts) && strpos($parts[0], '.') === false) {
            $baseUrl = '/' . $parts[0] . '/';
            return $baseUrl;
        }
    }

    $baseUrl = '/';
    return $baseUrl;
}

function app_url($path = '') {
    $path = ltrim($path, '/');
    $base = app_base_url();

    if ($path === '') {
        return $base;
    }

    return $base === '/' ? '/' . $path : $base . $path;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . app_url('login.php'));
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . app_url('index.php'));
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function getUserRole() {
    return $_SESSION['role'] ?? 'user';
}
