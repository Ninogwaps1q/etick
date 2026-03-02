<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? app_url('admin/dashboard.php') : app_url('user/dashboard.php'));
}

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            redirect($user['role'] === 'admin' ? app_url('admin/dashboard.php') : app_url('user/dashboard.php'));
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Sign In - eTick';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right text-primary"></i> Sign In
                    </h2>

                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <?php echo showAlert('Sign up successful! Please sign in.', 'success'); ?>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>

                    <hr class="my-4">

                    <p class="text-center mb-0">
                        Don't have an account? <a href="<?php echo app_url('register.php'); ?>">Sign Up here</a>
                    </p>

                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <strong>Admin Sign In:</strong><br>
                            Email: admin@etick.com<br>
                            Password: admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
