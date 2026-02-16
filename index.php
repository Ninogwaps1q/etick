<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$db = new Database();
$conn = $db->connect();

$eventsQuery = "SELECT * FROM events WHERE status = 'active' AND event_date > NOW() ORDER BY event_date ASC LIMIT 6";
$events = $conn->query($eventsQuery)->fetchAll();

$pageTitle = 'eTick - Event Ticketing System';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Welcome to eTick</h1>
                <p class="lead mb-4">Your one-stop platform for discovering and booking tickets to amazing events!</p>
                <div class="d-flex gap-3">
                    <a href="events.php" class="btn btn-light btn-lg">
                        <i class="bi bi-calendar-event"></i> Browse Events
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
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
            <h2 class="fw-bold">Upcoming Events</h2>
            <p class="text-muted">Discover exciting events happening near you</p>
        </div>
    </div>

    <?php if (empty($events)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No upcoming events at the moment. Check back soon!
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($events as $event): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <?php if ($event['image']): ?>
                            <img src="uploads/events/<?php echo $event['image']; ?>"
                                 class="card-img-top" alt="<?php echo $event['title']; ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                 style="height: 200px;">
                                <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $event['title']; ?></h5>
                            <p class="card-text text-muted">
                                <?php echo substr($event['description'], 0, 100) . (strlen($event['description']) > 100 ? '...' : ''); ?>
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
                                    <i class="bi bi-geo-alt"></i> <?php echo $event['location']; ?>
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 text-primary">₱<?php echo number_format($event['price'], 2); ?></span>
                                <span class="badge bg-info"><?php echo $event['available_tickets']; ?> tickets left</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <!-- Corrected path for XAMPP -->
                            <a href="event-details.php?id=<?php echo $event['id']; ?>"
                               class="btn btn-primary w-100">
                                <i class="bi bi-ticket"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="events.php" class="btn btn-outline-primary btn-lg">
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
                    <i class="bi bi-search text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Discover Events</h4>
                    <p class="text-muted">Browse through a wide variety of exciting events</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-ticket-perforated text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Easy Booking</h4>
                    <p class="text-muted">Book your tickets quickly and securely</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <i class="bi bi-phone text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Digital Tickets</h4>
                    <p class="text-muted">Get instant confirmation and manage bookings</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

