<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';

requireLogin(); // Make sure the user is logged in

$db = new Database();
$conn = $db->connect();

$userId = getUserId();
$success = '';
$error = '';

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found (shouldn't happen if logged in), redirect
if (!$user) {
    redirect('index.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $profilePic = $user['profile_pic'] ?? null;

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $upload = uploadImage($_FILES['profile_pic'], __DIR__ . '/uploads/profiles/');
        if ($upload['success']) {
            $profilePic = $upload['filename'];
            // Delete old profile picture if exists
            if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_pic'])) {
                unlink(__DIR__ . '/uploads/profiles/' . $user['profile_pic']);
            }
        } else {
            $error = $upload['message'];
        }
    }

    if (empty($error)) {
        // If password provided, hash it
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
            $params = [$name, $email, $hashedPassword, $profilePic, $userId];
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_pic = ? WHERE id = ?");
            $params = [$name, $email, $profilePic, $userId];
        }

        if ($stmt->execute($params)) {
            $success = 'Profile updated successfully!';
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

$pageTitle = 'My Profile - eTick';
require_once 'includes/header.php';
?>

<div class="container my-5">
    <h2 class="mb-4">My Profile</h2>

    <?php if ($success): ?>
        <?php echo showAlert($success, 'success'); ?>
    <?php endif; ?>
    <?php if ($error): ?>
        <?php echo showAlert($error, 'danger'); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm text-center p-3">
                <img src="uploads/profiles/<?php echo $user['profile_pic'] ?? 'default.png'; ?>"
                     class="img-fluid rounded-circle mb-3" style="width:150px; height:150px; object-fit:cover;" alt="Profile Picture">
                <h5><?php echo htmlspecialchars($user['u_name'] ?? ''); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user['u_email'] ?? ''); ?></p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?php echo htmlspecialchars($user['u_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($user['u_email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>

                    <div class="mb-3">
                        <label for="profile_pic" class="form-label">Profile Picture</label>
                        <input type="file" id="profile_pic" name="profile_pic" class="form-control" accept="image/*">
                        <small class="text-muted">Max 5MB. JPG, PNG, GIF allowed.</small>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
