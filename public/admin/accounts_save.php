<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_only.php';

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';
$accountStatus = $_POST['status'] ?? 'Active';
$imageName = trim($_POST['image_name'] ?? '');

if ($fullName === '' || $username === '' || $password === '' || $jobTitle === '' || $imageName === '' || !in_array($accountStatus, ['Active', 'Inactive'], true) || !in_array($role, ['user', 'supervisor', 'admin'], true)) {
    die('All required fields are invalid.');
}

$activeFlag = $accountStatus === 'Active' ? 1 : 0;
$password = md5($password);


$stmt = $conn->prepare('INSERT INTO accounts (full_name, username, password, image_name, role, active_flag, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('sssssis', $fullName, $username, $password, $imageName, $role, $activeFlag, $jobTitle);

if (!$stmt->execute()) {
    die('Create failed: ' . $stmt->error);
}

$newUserId = $stmt->insert_id;
$stmt->close();
logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Created account ID #' . $newUserId . ' for ' . $username);
header('Location: accounts.php');
exit;
