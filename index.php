<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$db = new Database();
$conn = $db->connect();

$upcomingEventsQuery = "
    SELECT *
    FROM events
    WHERE status = 'active' AND event_date > NOW()
    ORDER BY event_date ASC
    LIMIT 9
";
$upcomingEvents = $conn->query($upcomingEventsQuery)->fetchAll();

$nearEventsQuery = "
    SELECT *
    FROM events
    WHERE status = 'active'
      AND event_date > NOW()
      AND event_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY event_date ASC
    LIMIT 3
";
$nearEvents = $conn->query($nearEventsQuery)->fetchAll();

$soonEventsQuery = "
    SELECT *
    FROM events
    WHERE status = 'active'
      AND event_date > DATE_ADD(NOW(), INTERVAL 7 DAY)
      AND event_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
    ORDER BY event_date ASC
    LIMIT 3
";
$soonEvents = $conn->query($soonEventsQuery)->fetchAll();

$pageTitle = 'eTick - Event Ticketing System';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Welcome to eTick</h1>
                <p class="lead mb-4">Discover upcoming events, book your preferred ticket type, and manage your digital tickets in one place.</p>
                <div class="d-flex gap-3">
                    <a href="<?php echo app_url('events.php'); ?>" class="btn btn-light btn-lg">
                        <i class="bi bi-calendar-event"></i> Browse Events
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo app_url('register.php'); ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-person-plus"></i> Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="bi bi-ticket-perforated" style="font-size: 15rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold">Near Events (Next 7 Days)</h2>
            <p class="text-muted">Events happening very soon so you can book quickly.</p>
        </div>
    </div>

    <?php if (empty($nearEvents)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No near events in the next 7 days.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($nearEvents as $event): ?>
                <div class="col-md-4">
                    <a href="<?php echo app_url('event-details.php?id=' . (int) $event['id']); ?>" class="text-decoration-none">
                        <div class="near-event-card shadow-sm h-100">
                            <?php if (!empty($event['image'])): ?>
                                <img src="<?php echo app_url('uploads/events/' . $event['image']); ?>"
                                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                                     class="near-event-image">
                            <?php else: ?>
                                <div class="near-event-image-placeholder">
                                    <i class="bi bi-image fs-1 text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="near-event-content">
                                <h5 class="mb-1 text-dark"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="mb-2 text-muted small">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-calendar-event"></i> <?php echo date('M j, Y - g:i A', strtotime($event['event_date'])); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold">Coming This Month (8 to 30 Days)</h2>
            <p class="text-muted">Plan ahead with events happening later this month.</p>
        </div>
    </div>

    <?php if (empty($soonEvents)): ?>
        <div class="alert alert-light border text-center">
            <i class="bi bi-calendar-x"></i> No scheduled events in this range yet.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($soonEvents as $event): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <?php if (!empty($event['image'])): ?>
                            <img src="<?php echo app_url('uploads/events/' . $event['image']); ?>"
                                 class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text text-muted mb-2">
                                <?php
                                $desc = trim((string) ($event['description'] ?? ''));
                                echo htmlspecialchars(mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc);
                                ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-calendar"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                <span class="ms-2"><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['event_date'])); ?></span>
                            </p>
                            <p class="mb-2 text-muted small">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">PHP <?php echo number_format((float) $event['price'], 2); ?></span>
                                <span class="badge bg-info"><?php echo (int) $event['available_tickets']; ?> left</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="<?php echo app_url('event-details.php?id=' . (int) $event['id']); ?>" class="btn btn-primary w-100">
                                <i class="bi bi-ticket"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold">All Upcoming Events</h2>
            <p class="text-muted">Discover more events and secure your seat early.</p>
        </div>
    </div>

    <?php if (empty($upcomingEvents)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No upcoming events at the moment. Check back soon.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <?php if (!empty($event['image'])): ?>
                            <img src="<?php echo app_url('uploads/events/' . $event['image']); ?>"
                                 class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text text-muted">
                                <?php
                                $desc = trim((string) ($event['description'] ?? ''));
                                echo htmlspecialchars(mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc);
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i>
                                    <?php echo date('g:i A', strtotime($event['event_date'])); ?>
                                </small>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">PHP <?php echo number_format((float) $event['price'], 2); ?></span>
                                <span class="badge bg-info"><?php echo (int) $event['available_tickets']; ?> tickets left</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="<?php echo app_url('event-details.php?id=' . (int) $event['id']); ?>" class="btn btn-primary w-100">
                                <i class="bi bi-ticket"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo app_url('events.php'); ?>" class="btn btn-outline-primary btn-lg">
                View All Events <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="bg-light py-5 mt-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-calendar-week text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Near and Upcoming</h4>
                    <p class="text-muted">See near-term and monthly event sections directly on the homepage.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-ticket-perforated text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Choose Ticket Type</h4>
                    <p class="text-muted">Book by ticket category such as Regular, Premium, or VIP.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-qr-code text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">QR Digital Tickets</h4>
                    <p class="text-muted">Access each confirmed ticket with its own QR code in your account.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
