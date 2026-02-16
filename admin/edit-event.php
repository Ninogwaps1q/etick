<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    redirect(app_url('admin/events.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $eventDate = $_POST['event_date'];
    $location = sanitize($_POST['location']);
    $totalTickets = (int)$_POST['total_tickets'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'];

    $imagePath = $event['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $imagePath = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    if (empty($error)) {
        $ticketDiff = $totalTickets - $event['total_tickets'];
        $newAvailable = $event['available_tickets'] + $ticketDiff;

        $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, total_tickets = ?, available_tickets = ?, price = ?, image = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $eventDate, $location, $totalTickets, $newAvailable, $price, $imagePath, $status, $eventId])) {
            $success = 'Event updated successfully!';
            $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
        } else {
            $error = 'Failed to update event.';
        }
    }
}

$pageTitle = 'Edit Event - eTick Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-pencil"></i> Edit Event</h2>
                <a href="<?php echo app_url('admin/events.php'); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Events
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <?php echo showAlert($success, 'success'); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <?php echo showAlert($error, 'danger'); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?php echo $event['title']; ?>" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo $event['description']; ?></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="event_date" class="form-label">Event Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="event_date" name="event_date"
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location"
                                       value="<?php echo $event['location']; ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="total_tickets" class="form-label">Total Tickets *</label>
                                <input type="number" class="form-control" id="total_tickets" name="total_tickets"
                                       value="<?php echo $event['total_tickets']; ?>" min="1" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Ticket Price *</label>
                                <input type="number" class="form-control" id="price" name="price"
                                       value="<?php echo $event['price']; ?>" step="0.01" min="0" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $event['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="image" class="form-label">Event Image</label>
                                <?php if ($event['image']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo app_url('uploads/events/' . $event['image']); ?>"
                                             alt="Current Image" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image. Max 5MB.</small>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Event
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Event Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Available Tickets</small>
                        <h4><?php echo $event['available_tickets']; ?> / <?php echo $event['total_tickets']; ?></h4>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Tickets Sold</small>
                        <h4><?php echo $event['total_tickets'] - $event['available_tickets']; ?></h4>
                    </div>
                    <div>
                        <small class="text-muted">Revenue</small>
                        <h4><?php echo formatPrice(($event['total_tickets'] - $event['available_tickets']) * $event['price']); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
