<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$password = $_POST['password'] ?? '';
$activeFlag = filter_input(INPUT_POST, 'active_flag', FILTER_VALIDATE_INT);
$adminFlag = filter_input(INPUT_POST, 'admin_flag', FILTER_VALIDATE_INT);

if (!$userId || $fullName === '' || $username === '' || !in_array($activeFlag, [0, 1], true) || !in_array($adminFlag, [0, 1], true)) {
    die('All required fields are invalid.');
}

$currentStmt = $conn->prepare('SELECT full_name, username, job_title, active_flag, admin_flag FROM accounts WHERE user_id = ?');
$currentStmt->bind_param('i', $userId);
$currentStmt->execute();
$currentAccount = $currentStmt->get_result()->fetch_assoc();
$currentStmt->close();

if (!$currentAccount) {
    die('Account not found.');
}

if ($password !== '') {
    $password = md5($password);
    $stmt = $conn->prepare('UPDATE accounts SET full_name = ?, username = ?, job_title = ?, password = ?, active_flag = ?, admin_flag = ? WHERE user_id = ?');
    $bindTypes = 'ssssiii';
} else {
    $stmt = $conn->prepare('UPDATE accounts SET full_name = ?, username = ?, job_title = ?, active_flag = ?, admin_flag = ? WHERE user_id = ?');
    $bindTypes = 'sssiii';
}

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

if ($password !== '') {
    $stmt->bind_param($bindTypes, $fullName, $username, $jobTitle, $password, $activeFlag, $adminFlag, $userId);
} else {
    $stmt->bind_param($bindTypes, $fullName, $username, $jobTitle, $activeFlag, $adminFlag, $userId);
}

if (!$stmt->execute()) {
    die('Update failed: ' . $stmt->error);
}
$stmt->close();

$changes = [];
if ($currentAccount['full_name'] !== $fullName) {
    $changes[] = 'Full name changed';
}
if ($currentAccount['username'] !== $username) {
    $changes[] = 'Username changed';
}
if ($currentAccount['job_title'] !== $jobTitle) {
    $changes[] = 'Job title changed';
}
if ((int) $currentAccount['active_flag'] !== $activeFlag) {
    $changes[] = 'Status changed';
}
if ((int) $currentAccount['admin_flag'] !== $adminFlag) {
    $changes[] = 'Admin rights changed';
}
if ($password !== '') {
    $changes[] = 'Password changed';
}

logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', 'Updated Account ID #' . $userId . ': ' . ($changes ? implode('; ', $changes) : 'No changes'));
header('Location: accounts.php');
exit;
