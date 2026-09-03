<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$fullName = trim($_POST['full_name'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$status = $_POST['status'] ?? '';

if (!$userId || $fullName === '' || $jobTitle === '' || !in_array($status, ['0', '1'], true)) {
    die('All fields are required.');
}

$fullName = ucwords(strtolower($fullName));
$jobTitle = ucwords(strtolower($jobTitle));
$activeFlag = (int) $status;

$currentStmt = $conn->prepare(
    'SELECT full_name, job_title, active_flag FROM millers WHERE user_id = ?'
);

if (!$currentStmt) {
    die('Prepare failed: ' . $conn->error);
}

$currentStmt->bind_param('i', $userId);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentMiller = $currentResult->fetch_assoc();
$currentStmt->close();

if (!$currentMiller) {
    die('Miller record not found.');
}

$stmt = $conn->prepare(
    'UPDATE millers SET full_name = ?, job_title = ?, active_flag = ? WHERE user_id = ?'
);

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('ssii', $fullName, $jobTitle, $activeFlag, $userId);

if (!$stmt->execute()) {
    die('Update failed: ' . $stmt->error);
}

$username = $_SESSION['user'] ?? 'unknown';
$changes = [];

if ($currentMiller['full_name'] !== $fullName) {
    $changes[] = 'Full name: "' . $currentMiller['full_name'] . '" -> "' . $fullName . '"';
}

if ($currentMiller['job_title'] !== $jobTitle) {
    $changes[] = 'Job title: "' . $currentMiller['job_title'] . '" -> "' . $jobTitle . '"';
}

if ((int) $currentMiller['active_flag'] !== $activeFlag) {
    $oldStatus = (int) $currentMiller['active_flag'] === 1 ? 'Active' : 'Inactive';
    $newStatus = $activeFlag === 1 ? 'Active' : 'Inactive';
    $changes[] = 'Status: "' . $oldStatus . '" -> "' . $newStatus . '"';
}

$description = 'Updated Miller ID #' . $userId . ': ' . ($changes ? implode('; ', $changes) : 'No changes');
logAction($conn, $username ?? 'unknown', 'UPDATE', $description);

$stmt->close();
header('Location: millers.php');
exit;