<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();

$statsQuery = "
    SELECT
        (SELECT COUNT(*) FROM events WHERE status = 'active') as total_events,
        (SELECT COUNT(*) FROM bookings WHERE status = 'confirmed') as total_bookings,
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
        (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'confirmed') as total_revenue
";
$stats = $conn->query($statsQuery)->fetch();

$recentBookingsQuery = "
    SELECT b.*, e.title as event_title, u.full_name, u.email
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.booking_date DESC
    LIMIT 10
";
$recentBookings = $conn->query($recentBookingsQuery)->fetchAll();

$pageTitle = 'Admin Dashboard - eTick';
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo getUserName(); ?>!</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Events</h6>
                            <h2 class="mb-0"><?php echo $stats['total_events']; ?></h2>
                        </div>
                        <i class="bi bi-calendar-event fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Bookings</h6>
                            <h2 class="mb-0"><?php echo $stats['total_bookings']; ?></h2>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Users</h6>
                            <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                        </div>
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mb-0">₱<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                    </div>
                    <!-- Removed dollar icon -->
                </div>
            </div>
        </div>
</div>

    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-grid"></i> Quick Actions</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="/etick/admin/events.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-calendar-plus"></i> Manage Events
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="/etick/admin/bookings.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-ticket"></i> View Bookings
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="/etick/admin/users.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-people"></i> Manage Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Customer</th>
                                    <th>Event</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentBookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No bookings yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td><strong><?php echo $booking['booking_reference']; ?></strong></td>
                                            <td>
                                                <?php echo $booking['full_name']; ?><br>
                                                <small class="text-muted"><?php echo $booking['email']; ?></small>
                                            </td>
                                            <td><?php echo $booking['event_title']; ?></td>
                                            <td><?php echo $booking['ticket_quantity']; ?></td>
                                            <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td><?php echo formatDate($booking['booking_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
