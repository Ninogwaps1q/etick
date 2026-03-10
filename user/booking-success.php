<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$baseUrl = app_base_url();
$bookingRef = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

if (!$bookingRef) {
    redirect($baseUrl . 'user/dashboard.php');
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT b.*, e.title, e.event_date, e.location
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.booking_reference = ? AND b.user_id = ?
");
$stmt->execute([$bookingRef, getUserId()]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect($baseUrl . 'user/dashboard.php');
}

$isPending = ($booking['status'] === 'pending');
$isCancelled = ($booking['status'] === 'cancelled');
$ticketType = !empty($booking['ticket_type']) ? $booking['ticket_type'] : 'Regular';
$unitPrice = (float) ($booking['unit_price'] ?? 0);

$pageTitle = $isPending ? 'Booking Pending - eTick' : 'Booking Details - eTick';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <?php if ($isPending): ?>
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 5rem;"></i>
                        <?php elseif ($isCancelled): ?>
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
                        <?php else: ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <?php endif; ?>
                    </div>

                    <h2 class="mb-3 <?php echo $isPending ? 'text-warning' : ($isCancelled ? 'text-danger' : 'text-success'); ?>">
                        <?php echo $isPending ? 'Booking Pending Approval' : ($isCancelled ? 'Booking Cancelled' : 'Booking Confirmed'); ?>
                    </h2>
                    <p class="lead text-muted mb-4">
                        <?php if ($isPending): ?>
                            Your booking has been submitted and is waiting for confirmation.
                        <?php elseif ($isCancelled): ?>
                            This booking has been cancelled. Contact support if you need assistance.
                        <?php else: ?>
                            Your tickets have been successfully booked.
                        <?php endif; ?>
                    </p>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h4 class="mb-4">Booking Details</h4>

                            <div class="row text-start">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Booking Reference</small>
                                    <strong class="text-primary fs-5"><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Event</small>
                                    <strong><?php echo htmlspecialchars($booking['title']); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Event Date and Time</small>
                                    <strong><?php echo formatDate($booking['event_date']); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Location</small>
                                    <strong><?php echo htmlspecialchars($booking['location']); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Ticket Type</small>
                                    <strong><?php echo htmlspecialchars($ticketType); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Number of Tickets</small>
                                    <strong><?php echo (int) $booking['ticket_quantity']; ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Unit Price</small>
                                    <strong>PHP <?php echo number_format($unitPrice, 2); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Status</small>
                                    <strong class="<?php echo $isPending ? 'text-warning' : ($isCancelled ? 'text-danger' : 'text-success'); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Total Amount</small>
                                    <strong class="text-success"><?php echo formatPrice($booking['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info text-start">
                        <i class="bi bi-info-circle"></i>
                        <strong>Important:</strong> Please save your booking reference number.
                        You can view all your bookings in your dashboard.
                    </div>

                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="<?php echo $baseUrl; ?>user/dashboard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-speedometer2"></i> Go to Dashboard
                        </a>
                        <a href="<?php echo $baseUrl; ?>events.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-search"></i> Browse More Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
