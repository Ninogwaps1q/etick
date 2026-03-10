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
$allowedRoles = ['customer', 'organizer', 'admin'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'update_role') {
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $targetRole = normalizeUserRole(sanitize($_POST['role'] ?? ''));

    if ($targetUserId <= 0 || !in_array($targetRole, $allowedRoles, true)) {
        $error = 'Invalid role update request.';
    } else {
        $stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$targetUserId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            $error = 'User not found.';
        } else {
            $currentRole = normalizeUserRole($targetUser['role']);

            if ($targetUserId === (int) getUserId() && $targetRole !== 'admin') {
                $error = 'You cannot remove your own admin access.';
            } elseif ($currentRole === $targetRole) {
                $success = 'Role is already set to ' . ucfirst($targetRole) . '.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$targetRole, $targetUserId])) {
                    $success = 'User role updated successfully.';
                } else {
                    $error = 'Failed to update user role.';
                }
            }
        }
    }
}

$usersQuery = "
    SELECT u.*,
           CASE WHEN u.role = 'user' THEN 'customer' ELSE u.role END as normalized_role,
           (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as total_bookings,
           (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = u.id AND status = 'confirmed') as total_spent
    FROM users u
    ORDER BY u.created_at DESC
";
$users = $conn->query($usersQuery)->fetchAll();

$pageTitle = 'Manage Users - eTick Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-people"></i> Manage Users</h2>
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
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Update Role</th>
                                    <th>Total Bookings</th>
                                    <th>Total Spent</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                            $normalizedRole = $user['normalized_role'];
                                            $roleBadgeClass = $normalizedRole === 'admin'
                                                ? 'bg-danger'
                                                : ($normalizedRole === 'organizer' ? 'bg-warning text-dark' : 'bg-primary');
                                            $isCurrentUser = (int) $user['id'] === (int) getUserId();
                                        ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '-'; ?></td>
                                            <td>
                                                <span class="badge <?php echo $roleBadgeClass; ?>">
                                                    <?php echo ucfirst($normalizedRole); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                    <select name="role" class="form-select form-select-sm" <?php echo $isCurrentUser ? 'disabled' : ''; ?>>
                                                        <option value="customer" <?php echo $normalizedRole === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                        <option value="organizer" <?php echo $normalizedRole === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                                        <option value="admin" <?php echo $normalizedRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="badge bg-secondary">Current User</span>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                            <td><?php echo (int) $user['total_bookings']; ?></td>
                                            <td>&#8369;<?php echo number_format((float) $user['total_spent'], 2); ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
