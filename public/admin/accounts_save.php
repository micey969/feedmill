<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$password = $_POST['password'] ?? '';
$adminRole = $_POST['role'] ?? 'User';
$accountStatus = $_POST['status'] ?? 'Active';
$imageName = trim($_POST['image_name'] ?? '');

if ($fullName === '' || $username === '' || $password === '' || $jobTitle === '' || $imageName === '' || !in_array($accountStatus, ['Active', 'Inactive'], true) || !in_array($adminRole, ['Administrator', 'User'], true)) {
    die('All required fields are invalid.');
}

$adminFlag = $adminRole === 'Administrator' ? 1 : 0;
$activeFlag = $accountStatus === 'Active' ? 1 : 0;
$password = md5($password);


$stmt = $conn->prepare('INSERT INTO accounts (full_name, username, password, image_name, admin_flag, active_flag, job_title) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('ssssiis', $fullName, $username, $password, $imageName, $adminFlag, $activeFlag, $jobTitle);

if (!$stmt->execute()) {
    die('Create failed: ' . $stmt->error);
}

$newUserId = $stmt->insert_id;
$stmt->close();
logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Created account ID #' . $newUserId . ' for ' . $username);
header('Location: accounts.php');
exit;
