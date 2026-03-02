<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$baseUrl = app_base_url();

$db = new Database();
$conn = $db->connect();

$userId = getUserId();

// Fetch bookings
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

// Fetch confirmed ticket details for QR display
$ticketsQuery = "
    SELECT b.booking_reference, b.ticket_quantity, b.total_amount, b.booking_date, e.title, e.event_date, e.location
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($ticketsQuery);
$stmt->execute([$userId]);
$ticketDetails = $stmt->fetchAll();

// Fetch stats
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
            <p class="text-muted">Welcome back, <?php echo getUserName(); ?>!</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow">
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
                            <h2 class="mb-0"><?php echo $stats['total_tickets']; ?></h2>
                            <small class="text-white-50">Click to view ticket info + QR codes</small>
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
                            <h2 class="mb-0">₱<?php echo number_format($stats['total_spent'], 2); ?></h2>
                        </div>
                        <i class="fs-1 bi bi-currency-dollar" style="display:none;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse mb-4" id="ticketDetailsSection">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-qr-code"></i> Ticket Information and QR Codes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($ticketDetails)): ?>
                    <div class="alert alert-warning mb-0">
                        No confirmed tickets yet. Book an event to generate your QR ticket.
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($ticketDetails as $ticket): ?>
                            <?php
                                $qrPayload = 'Reference: ' . $ticket['booking_reference']
                                    . ' | Event: ' . $ticket['title']
                                    . ' | Date: ' . formatDate($ticket['event_date'])
                                    . ' | Location: ' . $ticket['location']
                                    . ' | Tickets: ' . $ticket['ticket_quantity'];
                            ?>
                            <div class="col-md-6">
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-8">
                                                <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($ticket['title']); ?></h6>
                                                <p class="mb-1"><small><i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($ticket['booking_reference']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-calendar"></i> <?php echo formatDate($ticket['event_date']); ?></small></p>
                                                <p class="mb-1"><small><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ticket['location']); ?></small></p>
                                                <p class="mb-0"><small><strong>Tickets:</strong> <?php echo (int) $ticket['ticket_quantity']; ?></small></p>
                                            </div>
                                            <div class="col-4 text-end">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo rawurlencode($qrPayload); ?>"
                                                     alt="QR code for <?php echo htmlspecialchars($ticket['booking_reference']); ?>"
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
                            <p class="text-muted">Start exploring events and book your tickets!</p>
                            <a href="<?= $baseUrl ?>events.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Browse Events
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <?php if ($booking['image']): ?>
                                                    <img src="<?= $baseUrl ?>uploads/events/<?php echo $booking['image']; ?>"
                                                         class="img-fluid h-100" alt="<?php echo $booking['title']; ?>"
                                                         style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary h-100 d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-white fs-1"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo $booking['title']; ?></h5>

                                                    <p class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar"></i>
                                                            <?php echo formatDate($booking['event_date']); ?>
                                                        </small>
                                                    </p>

                                                    <p class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-geo-alt"></i>
                                                            <?php echo $booking['location']; ?>
                                                        </small>
                                                    </p>

                                                    <hr>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Booking Ref:</span>
                                                        <strong class="text-primary"><?php echo $booking['booking_reference']; ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Tickets:</span>
                                                        <strong><?php echo $booking['ticket_quantity']; ?></strong>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Total:</span>
                                                        <strong>₱<?php echo number_format($booking['total_amount'], 2); ?></strong>
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

