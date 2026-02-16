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

// Fetch booking details
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

$pageTitle = 'Booking Confirmed - eTick';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>

                    <h2 class="text-success mb-3">Booking Confirmed!</h2>
                    <p class="lead text-muted mb-4">
                        Your tickets have been successfully booked. You will receive a confirmation email shortly.
                    </p>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h4 class="mb-4">Booking Details</h4>

                            <div class="row text-start">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Booking Reference</small>
                                    <strong class="text-primary fs-5"><?php echo $booking['booking_reference']; ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Event</small>
                                    <strong><?php echo $booking['title']; ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Event Date & Time</small>
                                    <strong><?php echo formatDate($booking['event_date']); ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Location</small>
                                    <strong><?php echo $booking['location']; ?></strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Number of Tickets</small>
                                    <strong><?php echo $booking['ticket_quantity']; ?></strong>
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
                        <a href="<?= $baseUrl ?>user/dashboard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-speedometer2"></i> Go to Dashboard
                        </a>
                        <a href="<?= $baseUrl ?>events.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-search"></i> Browse More Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

