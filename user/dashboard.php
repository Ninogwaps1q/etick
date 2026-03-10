<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$baseUrl = app_base_url();

$db = new Database();
$conn = $db->connect();

$userId = getUserId();

$bookingsQuery = "
    SELECT b.*, e.title, e.event_date, e.location, e.image
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($bookingsQuery);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$ticketBookingsQuery = "
    SELECT b.id, b.booking_reference, b.ticket_type, b.unit_price, b.ticket_quantity, b.total_amount, b.booking_date,
           e.title, e.event_date, e.location
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($ticketBookingsQuery);
$stmt->execute([$userId]);
$ticketBookings = $stmt->fetchAll();

$individualTickets = [];
foreach ($ticketBookings as $ticketBooking) {
    $ticketCount = max(1, (int) $ticketBooking['ticket_quantity']);

    for ($ticketNumber = 1; $ticketNumber <= $ticketCount; $ticketNumber++) {
        $ticketCode = $ticketBooking['booking_reference'] . '-' . str_pad((string) $ticketNumber, 2, '0', STR_PAD_LEFT);
        $ticketType = !empty($ticketBooking['ticket_type']) ? $ticketBooking['ticket_type'] : 'Regular';
        $unitPrice = (float) ($ticketBooking['unit_price'] ?? 0);

        $qrPayload = 'Ticket Code: ' . $ticketCode
            . ' | Booking Ref: ' . $ticketBooking['booking_reference']
            . ' | Event: ' . $ticketBooking['title']
            . ' | Ticket Type: ' . $ticketType
            . ' | Date: ' . formatDate($ticketBooking['event_date'])
            . ' | Location: ' . $ticketBooking['location'];

        $individualTickets[] = [
            'event_title' => $ticketBooking['title'],
            'event_date' => $ticketBooking['event_date'],
            'location' => $ticketBooking['location'],
            'booking_reference' => $ticketBooking['booking_reference'],
            'ticket_type' => $ticketType,
            'unit_price' => $unitPrice,
            'ticket_number' => $ticketNumber,
            'ticket_code' => $ticketCode,
            'qr_payload' => $qrPayload,
        ];
    }
}

$statsQuery = "
    SELECT
        (SELECT COUNT(*) FROM bookings WHERE user_id = ?) as total_bookings,
        (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = ? AND status = 'confirmed') as total_spent,
        (SELECT COALESCE(SUM(ticket_quantity), 0) FROM bookings WHERE user_id = ? AND status = 'confirmed') as total_tickets
";
$stmt = $conn->prepare($statsQuery);
$stmt->execute([$userId, $userId, $userId]);
$stats = $stmt->fetch();

$pageTitle = 'My Bookings - eTick';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-person-circle"></i> My Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars(getUserName()); ?>!</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Bookings</h6>
                            <h2 class="mb-0"><?php echo (int) $stats['total_bookings']; ?></h2>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <button type="button"
                    class="card bg-success text-white shadow total-tickets-toggle border-0 w-100 text-start"
                    data-bs-toggle="collapse"
                    data-bs-target="#ticketDetailsSection"
                    aria-expanded="false"
                    aria-controls="ticketDetailsSection">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Tickets</h6>
                            <h2 class="mb-0"><?php echo (int) $stats['total_tickets']; ?></h2>
                            <small class="text-white-50">Click to view each ticket QR and details</small>
                        </div>
                        <i class="bi bi-ticket-detailed fs-1"></i>
                    </div>
                </div>
            </button>
        </div>

        <div class="col-md-4">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Spent</h6>
                            <h2 class="mb-0">&#8369;<?php echo number_format((float) $stats['total_spent'], 2); ?></h2>
                        </div>
                        <i class="bi bi-cash-stack fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse mb-4" id="ticketDetailsSection">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-qr-code"></i> Confirmed Tickets with QR Codes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($individualTickets)): ?>
                    <div class="alert alert-warning mb-0">
                        No confirmed tickets yet. Book an event to generate your QR tickets.
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mb-3">
                        <strong><?php echo count($individualTickets); ?></strong> ticket(s) ready for entry.
                    </div>
                    <div class="row g-3">
                        <?php foreach ($individualTickets as $ticket): ?>
                            <div class="col-md-6">
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($ticket['event_title']); ?></h6>
                                                <p class="mb-1"><small><i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($ticket['ticket_code']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-hash"></i> Booking: <?php echo htmlspecialchars($ticket['booking_reference']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-star"></i> <?php echo htmlspecialchars($ticket['ticket_type']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-calendar"></i> <?php echo formatDate($ticket['event_date']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ticket['location']); ?></small></p>
                                                <p class="mb-0"><small><strong>Price:</strong> &#8369;<?php echo number_format((float) $ticket['unit_price'], 2); ?></small></p>
                                            </div>
                                            <div class="col-4 text-end">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo rawurlencode($ticket['qr_payload']); ?>"
                                                     alt="QR code for <?php echo htmlspecialchars($ticket['ticket_code']); ?>"
                                                     class="img-fluid border rounded p-1 bg-white">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> My Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-ticket text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">No bookings yet</h4>
                            <p class="text-muted">Start exploring events and book your tickets.</p>
                            <a href="<?php echo $baseUrl; ?>events.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Browse Events
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border">
                                        <div class="row g-0 h-100">
                                            <div class="col-md-4">
                                                <?php if (!empty($booking['image'])): ?>
                                                    <img src="<?php echo $baseUrl; ?>uploads/events/<?php echo htmlspecialchars($booking['image']); ?>"
                                                         class="img-fluid h-100" alt="<?php echo htmlspecialchars($booking['title']); ?>"
                                                         style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary h-100 d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-white fs-1"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($booking['title']); ?></h5>

                                                    <p class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar"></i>
                                                            <?php echo formatDate($booking['event_date']); ?>
                                                        </small>
                                                    </p>

                                                    <p class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-geo-alt"></i>
                                                            <?php echo htmlspecialchars($booking['location']); ?>
                                                        </small>
                                                    </p>

                                                    <hr>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Booking Ref:</span>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Ticket Type:</span>
                                                        <strong><?php echo htmlspecialchars($booking['ticket_type'] ?: 'Regular'); ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Tickets:</span>
                                                        <strong><?php echo (int) $booking['ticket_quantity']; ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Unit Price:</span>
                                                        <strong>&#8369;<?php echo number_format((float) ($booking['unit_price'] ?? 0), 2); ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Total:</span>
                                                        <strong>&#8369;<?php echo number_format((float) $booking['total_amount'], 2); ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>Status:</span>
                                                        <span class="badge bg-<?php
                                                            echo $booking['status'] === 'confirmed' ? 'success' :
                                                                ($booking['status'] === 'cancelled' ? 'danger' : 'warning');
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
