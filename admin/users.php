<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->connect();

$usersQuery = "
    SELECT u.*,
           (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
           (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = u.id AND status = 'confirmed') as total_spent
    FROM users u
    ORDER BY u.created_at DESC
";
$users = $conn->query($usersQuery)->fetchAll();

$pageTitle = 'Manage Users - eTick Admin';
require_once '../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-people"></i> Manage Users</h2>
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
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Total Bookings</th>
                                    <th>Total Spent</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><strong><?php echo $user['full_name']; ?></strong></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['phone'] ?: '-'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['total_bookings']; ?></td>
                                            <td>₱<?php echo number_format($user['total_spent'], 2); ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
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

<?php require_once '../includes/footer.php'; ?>
