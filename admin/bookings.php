<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();
$success = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_status'])) {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $newStatus = sanitize($_POST['new_status'] ?? '');

    if ($bookingId <= 0 || !in_array($newStatus, ['confirmed', 'cancelled'], true)) {
        $error = 'Invalid booking status request.';
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT id, event_id, ticket_quantity, status FROM bookings WHERE id = ? FOR UPDATE");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                $conn->rollBack();
                $error = 'Booking not found.';
            } elseif ($booking['status'] !== 'pending') {
                $conn->rollBack();
                $error = 'Only pending bookings can be updated.';
            } else {
                if ($newStatus === 'cancelled') {
                    $stmt = $conn->prepare("UPDATE events SET available_tickets = available_tickets + ? WHERE id = ?");
                    $stmt->execute([(int) $booking['ticket_quantity'], (int) $booking['event_id']]);
                }

                $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $bookingId]);

                $conn->commit();
                $success = 'Booking status updated to ' . ucfirst($newStatus) . '.';
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Failed to update booking status. Please try again.';
        }
    }
}

$bookingsQuery = "
    SELECT b.*, e.title as event_title, u.full_name, u.email, u.phone
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.booking_date DESC
";
$bookings = $conn->query($bookingsQuery)->fetchAll();

$pageTitle = 'Manage Bookings - eTick Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-ticket-perforated"></i> Manage Bookings</h2>
            <?php if ($success): ?>
                <?php echo showAlert($success, 'success'); ?>
            <?php endif; ?>
            <?php if ($error): ?>
                <?php echo showAlert($error, 'danger'); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Customer Details</th>
                                    <th>Event</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Booking Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No bookings yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary"><?php echo $booking['booking_reference']; ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo $booking['full_name']; ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-envelope"></i> <?php echo $booking['email']; ?>
                                                        <?php if ($booking['phone']): ?>
                                                            <br><i class="bi bi-phone"></i> <?php echo $booking['phone']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td><?php echo $booking['event_title']; ?></td>
                                            <td><span class="badge bg-info"><?php echo $booking['ticket_quantity']; ?></span></td>
                                            <td><strong>₱<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                            <td><?php echo formatDate($booking['booking_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $booking['status'] === 'confirmed' ? 'success' :
                                                        ($booking['status'] === 'cancelled' ? 'danger' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <form method="POST" action="" class="d-flex gap-2">
                                                        <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm" required>
                                                            <option value="confirmed">Confirm</option>
                                                            <option value="cancelled">Cancel</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                                    </form>
                                                <?php else: ?>
                                                    <small class="text-muted">Processed</small>
                                                <?php endif; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

