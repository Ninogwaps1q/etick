<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Base URL for your project
$baseUrl = '/Etick/'; // adjust if your project folder is different
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'eTick - Event Ticketing System'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $baseUrl ?>index.php">
            <i class="bi bi-ticket-perforated"></i> eTick
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>events.php">Events</a></li>

                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>admin/dashboard.php">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>user/dashboard.php">My Bookings</a></li>
                    <?php endif; ?>

                    <!-- Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo getUserName(); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>register.php">Register</a></li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<main class="py-4">
