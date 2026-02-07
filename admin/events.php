<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $eventDate = $_POST['event_date'];
        $location = sanitize($_POST['location']);
        $totalTickets = (int)$_POST['total_tickets'];
        $price = (float)$_POST['price'];

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload = uploadImage($_FILES['image']);
            if ($upload['success']) {
                $imagePath = $upload['filename'];
            } else {
                $error = $upload['message'];
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, total_tickets, available_tickets, price, image, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $eventDate, $location, $totalTickets, $totalTickets, $price, $imagePath, getUserId()])) {
                $success = 'Event added successfully!';
            } else {
                $error = 'Failed to add event.';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $eventId = (int)$_POST['event_id'];
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$eventId])) {
            $success = 'Event deleted successfully!';
        } else {
            $error = 'Failed to delete event.';
        }
    } elseif ($_POST['action'] === 'update_status') {
        $eventId = (int)$_POST['event_id'];
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $eventId])) {
            $success = 'Event status updated!';
        } else {
            $error = 'Failed to update status.';
        }
    }
}

$eventsQuery = "SELECT * FROM events ORDER BY created_at DESC";
$events = $conn->query($eventsQuery)->fetchAll();

$pageTitle = 'Manage Events - eTick Admin';
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-calendar-event"></i> Manage Events</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="bi bi-plus-circle"></i> Add New Event
                </button>
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
        <div class="col">
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Price</th>
                                    <th>Tickets</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No events yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td>
                                                <?php if ($event['image']): ?>
                                                    <img src="/etick/uploads/events/<?php echo $event['image']; ?>"
                                                         alt="<?php echo $event['title']; ?>"
                                                         style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                                         data-bs-toggle="modal" data-bs-target="#imageModal"
                                                         data-img="/etick/uploads/events/<?php echo $event['image']; ?>">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #ddd;"></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $event['title']; ?></td>
                                            <td><?php echo formatDate($event['event_date']); ?></td>
                                            <td><?php echo $event['location']; ?></td>
                                            <td>₱<?php echo number_format($event['price'], 2); ?></td>
                                            <td><?php echo $event['available_tickets']; ?> / <?php echo $event['total_tickets']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $event['status'] === 'active' ? 'success' : ($event['status'] === 'cancelled' ? 'danger' : 'secondary');
                                                ?>">
                                                    <?php echo ucfirst($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/admin/edit-event.php?id=<?php echo $event['id']; ?>"
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger"
                                                            onclick="deleteEvent(<?php echo $event['id']; ?>)" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="event_date" class="form-label">Event Date & Time *</label>
                            <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="total_tickets" class="form-label">Total Tickets *</label>
                            <input type="number" class="form-control" id="total_tickets" name="total_tickets" min="1" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Ticket Price *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            <small class="text-muted">Enter amount in Peso (₱)</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="image" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">Max 5MB. Supported: JPG, PNG, GIF</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Add Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="event_id" id="deleteEventId">
</form>

<!-- Image View Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0">
                <img src="" id="modalImage" class="img-fluid w-100" alt="Event Image">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        document.getElementById('deleteEventId').value = eventId;
        document.getElementById('deleteForm').submit();
    }
}

// Image Modal Script
var imageModal = document.getElementById('imageModal');
imageModal.addEventListener('show.bs.modal', function (event) {
    var img = event.relatedTarget;
    var src = img.getAttribute('data-img');
    var modalImg = document.getElementById('modalImage');
    modalImg.src = src;
});
</script>

<?php require_once '../includes/footer.php'; ?>
