<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function formatDate($date) {
    return date('F j, Y g:i A', strtotime($date));
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function generateBookingReference() {
    return 'ETK' . strtoupper(substr(uniqid(), -8));
}

function uploadImage($file, $targetDir = null) {

    // build real path automatically
    if ($targetDir === null) {
        $targetDir = __DIR__ . '/../uploads/events/';
    }

    // create folder if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if ($file['error'] !== 0) {
        return ['success' => false, 'message' => 'Upload error.'];
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }

    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large. Max size is 5MB.'];
    }

    // unique name
    $fileName = time() . '_' . uniqid() . '.' . $imageFileType;

    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => true, 'filename' => $fileName];
    }

    return ['success' => false, 'message' => 'Error uploading file.'];
}


function showAlert($message, $type = 'info') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

function isStrongPassword($password) {
    // At least 8 chars with letter, number, and symbol.
    return (bool) preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
}

function isValidPhilippineMobile($phone) {
    return (bool) preg_match('/^\+63\d{10}$/', $phone);
}
