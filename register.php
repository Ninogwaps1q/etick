<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(app_url('index.php'));
}

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phoneInput = $_POST['phone'] ?? '';
    $phoneDigits = preg_replace('/\D+/', '', $phoneInput);
    if (strpos($phoneDigits, '63') === 0 && strlen($phoneDigits) === 12) {
        $phoneDigits = substr($phoneDigits, 2);
    }
    $phone = '+63' . $phoneDigits;
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($fullName) || empty($email) || empty($phoneDigits) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^9\d{9}$/', $phoneDigits)) {
        $error = 'Enter a valid mobile number using 10 digits only (example: 9123456789).';
    } elseif (!isValidPhilippineMobile($phone)) {
        $error = 'Mobile number must start with +63 and include 10 digits (e.g. +639123456789).';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password must be at least 8 characters and include letters, numbers, and symbols.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");

            if ($stmt->execute([$fullName, $email, $phone, $hashedPassword])) {
                redirect(app_url('login.php?registered=1'));
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Sign Up - eTick';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="bi bi-person-plus text-primary"></i> Sign Up
                    </h2>

                    <?php if ($error): ?>
                        <?php echo showAlert($error, 'danger'); ?>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Mobile Number *</label>
                            <div class="input-group">
                                <span class="input-group-text">+63</span>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($phoneDigits ?? ($_POST['phone'] ?? '')); ?>"
                                       placeholder="9123456789"
                                       pattern="^9\d{9}$"
                                       maxlength="10"
                                       inputmode="numeric"
                                       title="Enter 10 digits only, starting with 9."
                                       required>
                            </div>
                           
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   pattern="^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$"
                                   title="Use at least 8 characters with letters, numbers, and symbols."
                                   required>
                            <small class="text-muted">Use at least 8 characters with letters, numbers, and symbols.</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> Sign Up
                        </button>
                    </form>

                    <hr class="my-4">

                    <p class="text-center mb-0">
                        Already have an account? <a href="login.php">Sign In here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('phone');
    if (!phoneInput) {
        return;
    }

    phoneInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
