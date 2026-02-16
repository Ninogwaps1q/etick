<?php
require_once __DIR__ . '/config/session.php';

$_SESSION = [];
session_destroy();

header('Location: ' . app_url('login.php'));
exit();

