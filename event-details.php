<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$db = new Database();
$conn = $db->connect();

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = '';

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND status = 'active'");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    redirect(app_url('events.php'));
}

$ticketTypeOptions = getTicketTypeOptions();
$defaultTicketType = $ticketTypeOptions[0]['code'];
$selectedTicketType = $defaultTicketType;
$selectedPaymentMethod = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['book'])) {
    if (!isLoggedIn()) {
        redirect(app_url('login.php'));
    }

    $ticketQuantity = (int) ($_POST['ticket_quantity'] ?? 0);
    $selectedTicketType = sanitize($_POST['ticket_type'] ?? $defaultTicketType);
    $selectedPaymentMethod = sanitize($_POST['payment_method'] ?? '');

    $ticketType = getTicketTypeByCode($selectedTicketType);
    $selectedTicketType = $ticketType['code'];

    if ($ticketQuantity < 1) {
        $error = 'Please select at least 1 ticket.';
    } elseif ($ticketQuantity > (int) $event['available_tickets']) {
        $error = 'Not enough tickets available.';
    } elseif ($ticketQuantity > 10) {
        $error = 'Maximum 10 tickets per booking.';
    } elseif (empty($selectedPaymentMethod)) {
        $error = 'Please select a payment method.';
    } else {
        $unitPrice = calculateTicketUnitPrice((float) $event['price'], $selectedTicketType);
        $totalAmount = calculateTicketTotal((float) $event['price'], $selectedTicketType, $ticketQuantity);
        $bookingReference = generateBookingReference();

        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, ticket_type, unit_price, ticket_quantity, total_amount, booking_reference, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([
                getUserId(),
                $eventId,
                $selectedTicketType,
                $unitPrice,
                $ticketQuantity,
                $totalAmount,
                $bookingReference,
                $selectedPaymentMethod,
            ]);

            $stmt = $conn->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
            $stmt->execute([$ticketQuantity, $eventId]);

            $conn->commit();
            redirect(app_url('user/booking-success.php?ref=' . urlencode($bookingReference)));
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Booking failed. Please try again.';
        }
    }

    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
}

$ticketTypePrices = [];
$ticketTypeDescriptions = [];
foreach ($ticketTypeOptions as $ticketTypeOption) {
    $ticketTypePrices[$ticketTypeOption['code']] = calculateTicketUnitPrice((float) $event['price'], $ticketTypeOption['code']);
    $ticketTypeDescriptions[$ticketTypeOption['code']] = $ticketTypeOption['description'];
}

$selectedUnitPrice = calculateTicketUnitPrice((float) $event['price'], $selectedTicketType);

$pageTitle = $event['title'] . ' - eTick';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <?php if (!empty($event['image'])): ?>
                    <img src="<?php echo app_url('uploads/events/' . $event['image']); ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>"
                         style="max-height: 400px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h2>

                    <div class="row my-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-event text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Date and Time</small>
                                    <strong><?php echo formatDate($event['event_date']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-geo-alt text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Location</small>
                                    <strong><?php echo htmlspecialchars($event['location']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-ticket-perforated text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Available Tickets</small>
                                    <strong><?php echo (int) $event['available_tickets']; ?> / <?php echo (int) $event['total_tickets']; ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-currency-dollar text-primary fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Base Ticket Price</small>
                                    <strong class="text-primary">&#8369;<?php echo number_format((float) $event['price'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h4 class="mt-4">About This Event</h4>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
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

                    <?php if ((int) $event['available_tickets'] > 0): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="ticket_type" class="form-label">Ticket Type</label>
                                <select class="form-select" id="ticket_type" name="ticket_type" required>
                                    <?php foreach ($ticketTypeOptions as $ticketTypeOption): ?>
                                        <?php
                                            $ticketTypeCode = $ticketTypeOption['code'];
                                            $ticketTypePrice = $ticketTypePrices[$ticketTypeCode];
                                        ?>
                                        <option value="<?php echo htmlspecialchars($ticketTypeCode); ?>" <?php echo $selectedTicketType === $ticketTypeCode ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ticketTypeOption['label']); ?> - &#8369;<?php echo number_format($ticketTypePrice, 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" id="ticketTypeDescription"><?php echo htmlspecialchars($ticketTypeDescriptions[$selectedTicketType]); ?></small>
                            </div>

                            <div class="mb-3">
                                <label for="ticket_quantity" class="form-label">Number of Tickets</label>
                                <input type="number" class="form-control" id="ticket_quantity" name="ticket_quantity"
                                       value="<?php echo isset($_POST['ticket_quantity']) ? (int) $_POST['ticket_quantity'] : 1; ?>"
                                       min="1" max="<?php echo min(10, (int) $event['available_tickets']); ?>" required>
                                <small class="text-muted">Max 10 tickets per booking</small>
                            </div>

                            <div class="mb-3 d-flex justify-content-between">
                                <span>Price per ticket:</span>
                                <strong id="unitPriceDisplay">&#8369;<?php echo number_format($selectedUnitPrice, 2); ?></strong>
                            </div>

                            <div class="mb-3 d-flex justify-content-between">
                                <span>Total Amount:</span>
                                <strong id="totalAmount" class="text-primary">&#8369;<?php echo number_format($selectedUnitPrice, 2); ?></strong>
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">-- Select Payment Method --</option>
                                    <option value="GCash" <?php echo $selectedPaymentMethod === 'GCash' ? 'selected' : ''; ?>>GCash</option>
                                    <option value="Online Banking" <?php echo $selectedPaymentMethod === 'Online Banking' ? 'selected' : ''; ?>>Online Banking</option>
                                    <option value="Card" <?php echo $selectedPaymentMethod === 'Card' ? 'selected' : ''; ?>>Card (Visa/Master)</option>
                                </select>
                            </div>

                            <?php if (isLoggedIn()): ?>
                                <button type="submit" name="book" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-ticket"></i> Book Now
                                </button>
                            <?php else: ?>
                                <a href="<?php echo app_url('login.php'); ?>" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Book
                                </a>
                            <?php endif; ?>
                        </form>

                        <script>
                            const ticketTypePrices = <?php echo json_encode($ticketTypePrices); ?>;
                            const ticketTypeDescriptions = <?php echo json_encode($ticketTypeDescriptions); ?>;
                            const ticketTypeSelect = document.getElementById('ticket_type');
                            const ticketInput = document.getElementById('ticket_quantity');
                            const unitPriceDisplay = document.getElementById('unitPriceDisplay');
                            const totalAmountDisplay = document.getElementById('totalAmount');
                            const ticketTypeDescription = document.getElementById('ticketTypeDescription');

                            function updateBookingAmount() {
                                const selectedType = ticketTypeSelect.value;
                                const unitPrice = Number(ticketTypePrices[selectedType] || 0);
                                const quantity = Number.parseInt(ticketInput.value || '0', 10) || 0;
                                const total = unitPrice * quantity;

                                unitPriceDisplay.textContent = 'PHP ' + unitPrice.toFixed(2);
                                totalAmountDisplay.textContent = 'PHP ' + total.toFixed(2);
                                ticketTypeDescription.textContent = ticketTypeDescriptions[selectedType] || '';
                            }

                            ticketInput.addEventListener('input', updateBookingAmount);
                            ticketTypeSelect.addEventListener('change', updateBookingAmount);
                            updateBookingAmount();
                        </script>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Sorry, this event is sold out.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
