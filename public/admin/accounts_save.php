<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_only.php';

$fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
$username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
$jobTitle = is_string($_POST['job_title'] ?? null) ? trim($_POST['job_title']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$role = $_POST['role'] ?? 'user';
$accountStatus = $_POST['status'] ?? 'Active';
$imageName = is_string($_POST['image_name'] ?? null) ? trim($_POST['image_name']) : '';

if ($fullName === '' || $username === '' || $password === '' || $jobTitle === '' || $imageName === '' || !in_array($accountStatus, ['Active', 'Inactive'], true) || !in_array($role, ['user', 'supervisor', 'admin'], true)) {
    header('Location: ' . publicUrl('admin/accounts.php?error=invalid'));
    exit;
}

$activeFlag = $accountStatus === 'Active' ? 1 : 0;
$password = md5($password);


$stmt = null;
try {
    $stmt = $conn->prepare('INSERT INTO accounts (full_name, username, password, image_name, role, active_flag, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare account creation.');
    }
    $stmt->bind_param('sssssis', $fullName, $username, $password, $imageName, $role, $activeFlag, $jobTitle);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to create account.');
    }

    $newUserId = $stmt->insert_id;
    logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Created account ID #' . $newUserId . ' for ' . $username);
} catch (Throwable $error) {
    $status = $error instanceof mysqli_sql_exception && $error->getCode() === 1062 ? 'duplicate' : 'save';
    header('Location: ' . publicUrl('admin/accounts.php?error=' . $status));
    exit;
} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

header('Location: ' . publicUrl('admin/accounts.php?success=created'));
exit;
