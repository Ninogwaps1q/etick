<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$db = new Database();
$conn = $db->connect();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'active' AND event_date > NOW() AND (title LIKE ? OR location LIKE ? OR description LIKE ?) ORDER BY event_date ASC");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $events = $stmt->fetchAll();
} else {
    $eventsQuery = "SELECT * FROM events WHERE status = 'active' AND event_date > NOW() ORDER BY event_date ASC";
    $events = $conn->query($eventsQuery)->fetchAll();
}

$pageTitle = 'Browse Events - eTick';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-calendar-event"></i> Browse Events</h2>
            <p class="text-muted">Find your next amazing experience</p>
        </div>
        <div class="col-md-4">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search events..."
                           value="<?php echo $search; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($events)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i>
            <?php if ($search): ?>
                No events found matching "<?php echo $search; ?>". Try a different search term.
            <?php else: ?>
                No upcoming events at the moment. Check back soon!
            <?php endif; ?>
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
                            <a href="event-details.php?id=<?php echo $event['id']; ?>"
                               class="btn btn-primary w-100">
                                <i class="bi bi-ticket"></i> View Details & Book
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

