<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$db = new Database();
$conn = $db->connect();

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND status = 'active'");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    redirect('events.php'); // Redirect if event not found
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['book'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $ticketQuantity = (int)$_POST['ticket_quantity'];
    $paymentMethod = sanitize($_POST['payment_method']); // New: payment method

    if ($ticketQuantity < 1) {
        $error = 'Please select at least 1 ticket.';
    } elseif ($ticketQuantity > $event['available_tickets']) {
        $error = 'Not enough tickets available.';
    } elseif ($ticketQuantity > 10) {
        $error = 'Maximum 10 tickets per booking.';
    } elseif (empty($paymentMethod)) {
        $error = 'Please select a payment method.';
    } else {
        $totalAmount = $ticketQuantity * $event['price'];
        $bookingReference = generateBookingReference();

        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, ticket_quantity, total_amount, booking_reference, status, payment_method) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([getUserId(), $eventId, $ticketQuantity, $totalAmount, $bookingReference, $paymentMethod]);

            $stmt = $conn->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
            $stmt->execute([$ticketQuantity, $eventId]);

            $conn->commit();

            redirect("user/booking-success.php?ref=$bookingReference");
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Booking failed. Please try again.';
        }
    }

    // Refresh event data
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
}

$pageTitle = $event['title'] . ' - eTick';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <?php if ($event['image']): ?>
                    <img src="uploads/events/<?php echo $event['image']; ?>"
                         class="card-img-top" alt="<?php echo $event['title']; ?>"
                         style="max-height: 400px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $event['title']; ?></h2>

                    <div class="row my-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-event text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Date & Time</small>
                                    <strong><?php echo formatDate($event['event_date']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-geo-alt text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Location</small>
                                    <strong><?php echo $event['location']; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-ticket-perforated text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Available Tickets</small>
                                    <strong><?php echo $event['available_tickets']; ?> / <?php echo $event['total_tickets']; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-currency-dollar text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Ticket Price</small>
                                    <strong class="text-primary">₱<?php echo number_format($event['price'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h4 class="mt-4">About This Event</h4>
                    <p class="text-muted"><?php echo nl2br($event['description']); ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h4 class="card-title mb-4">Book Tickets</h4>

                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <?php if ($event['available_tickets'] > 0): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="ticket_quantity" class="form-label">Number of Tickets</label>
                                <input type="number" class="form-control" id="ticket_quantity" name="ticket_quantity"
                                       value="1" min="1" max="<?php echo min(10, $event['available_tickets']); ?>" required>
                                <small class="text-muted">Max 10 tickets per booking</small>
                            </div>

                            <div class="mb-3 d-flex justify-content-between">
                                <span>Price per ticket:</span>
                                <strong>₱<?php echo number_format($event['price'], 2); ?></strong>
                            </div>

                            <div class="mb-3 d-flex justify-content-between">
                                <span>Total Amount:</span>
                                <strong id="totalAmount" class="text-primary">₱<?php echo number_format($event['price'], 2); ?></strong>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">-- Select Payment Method --</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Online Banking">Online Banking</option>
                                    <option value="Card">Card (Visa/Master)</option>
                                </select>
                            </div>

                            <?php if (isLoggedIn()): ?>
                                <button type="submit" name="book" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-ticket"></i> Book Now
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Book
                                </a>
                            <?php endif; ?>
                        </form>

                        <script>
                            const pricePerTicket = <?php echo $event['price']; ?>;
                            const ticketInput = document.getElementById('ticket_quantity');
                            const totalAmount = document.getElementById('totalAmount');

                            ticketInput.addEventListener('input', function() {
                                const quantity = parseInt(this.value) || 0;
                                const total = quantity * pricePerTicket;
                                totalAmount.textContent = '₱' + total.toFixed(2);
                            });
                        </script>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Sorry, this event is sold out!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
