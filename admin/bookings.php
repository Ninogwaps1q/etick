<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();

$bookingsQuery = "
    SELECT b.*, e.title as event_title, u.full_name, u.email, u.phone
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.booking_date DESC
";
$bookings = $conn->query($bookingsQuery)->fetchAll();

$pageTitle = 'Manage Bookings - eTick Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-ticket-perforated"></i> Manage Bookings</h2>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Customer Details</th>
                                    <th>Event</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Booking Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No bookings yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary"><?php echo $booking['booking_reference']; ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo $booking['full_name']; ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-envelope"></i> <?php echo $booking['email']; ?>
                                                        <?php if ($booking['phone']): ?>
                                                            <br><i class="bi bi-phone"></i> <?php echo $booking['phone']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td><?php echo $booking['event_title']; ?></td>
                                            <td><span class="badge bg-info"><?php echo $booking['ticket_quantity']; ?></span></td>
                                            <td><strong>₱<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                            <td><?php echo formatDate($booking['booking_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $booking['status'] === 'confirmed' ? 'success' :
                                                        ($booking['status'] === 'cancelled' ? 'danger' : 'warning');
                                                ?>">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

