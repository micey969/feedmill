<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
$jobTitle = is_string($_POST['job_title'] ?? null) ? trim($_POST['job_title']) : '';
$status = $_POST['status'] ?? '';

if (!$userId || $fullName === '' || !in_array($jobTitle, ['Mill Operator', 'Assistant Senior Miller', 'Shift Supervisor', 'Assistant Mill Supervisor'], true) || !in_array($status, ['0', '1'], true)) {
    header('Location: ' . publicUrl('admin/millers.php?error=invalid'));
    exit;
}

$fullName = ucwords(strtolower($fullName));
$jobTitle = ucwords(strtolower($jobTitle));
$activeFlag = (int) $status;

$currentStmt = null;
$stmt = null;
try {
    $currentStmt = $conn->prepare('SELECT full_name, job_title, active_flag FROM millers WHERE user_id = ?');
    if (!$currentStmt) {
        throw new RuntimeException('Unable to load miller record.');
    }
    $currentStmt->bind_param('i', $userId);
    $currentStmt->execute();
    $currentMiller = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();
    $currentStmt = null;

    if (!$currentMiller) {
        header('Location: ' . publicUrl('admin/millers.php?error=not_found'));
        exit;
    }

    $stmt = $conn->prepare('UPDATE millers SET full_name = ?, job_title = ?, active_flag = ? WHERE user_id = ?');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare miller update.');
    }
    $stmt->bind_param('ssii', $fullName, $jobTitle, $activeFlag, $userId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to update miller record.');
    }
} catch (Throwable $error) {
    header('Location: ' . publicUrl('admin/millers.php?error=save'));
    exit;
} finally {
    if ($currentStmt instanceof mysqli_stmt) {
        $currentStmt->close();
    }
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
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

$description = 'Updated Miller ID #' . $userId . ': ' . ($changes ? "\n" . implode("\n", $changes) : 'No changes');
logAction($conn, $username ?? 'unknown', 'UPDATE', $description);

header('Location: ' . publicUrl('admin/millers.php?success=updated'));
exit;