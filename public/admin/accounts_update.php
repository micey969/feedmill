<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_only.php';

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
$username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
$jobTitle = is_string($_POST['job_title'] ?? null) ? trim($_POST['job_title']) : '';
$imageName = is_string($_POST['image_name'] ?? null) ? trim($_POST['image_name']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$activeFlag = filter_input(INPUT_POST, 'active_flag', FILTER_VALIDATE_INT);
$role = $_POST['role'] ?? '';

if (!$userId || $fullName === '' || $jobTitle === '' || $username === '' || !in_array($activeFlag, [0, 1], true) || !in_array($role, ['user', 'supervisor', 'admin'], true)) {
    header('Location: ' . publicUrl('admin/accounts.php?error=invalid'));
    exit;
}

$currentStmt = null;
$stmt = null;
try {
    $currentStmt = $conn->prepare('SELECT full_name, username, job_title, image_name, active_flag, role FROM accounts WHERE user_id = ?');
    if (!$currentStmt) {
        throw new RuntimeException('Unable to load account.');
    }
    $currentStmt->bind_param('i', $userId);
    $currentStmt->execute();
    $currentAccount = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();
    $currentStmt = null;

    if (!$currentAccount) {
        header('Location: ' . publicUrl('admin/accounts.php?error=not_found'));
        exit;
    }

    if ($password !== '') {
        $password = md5($password);
        $stmt = $conn->prepare('UPDATE accounts SET full_name = ?, username = ?, job_title = ?, image_name = ?, password = ?, active_flag = ?, role = ? WHERE user_id = ?');
        $bindTypes = 'sssssisi';
    } else {
        $stmt = $conn->prepare('UPDATE accounts SET full_name = ?, username = ?, job_title = ?, image_name = ?, active_flag = ?, role = ? WHERE user_id = ?');
        $bindTypes = 'ssssisi';
    }

    if (!$stmt) {
        throw new RuntimeException('Unable to prepare account update.');
    }
    if ($password !== '') {
        $stmt->bind_param($bindTypes, $fullName, $username, $jobTitle, $imageName, $password, $activeFlag, $role, $userId);
    } else {
        $stmt->bind_param($bindTypes, $fullName, $username, $jobTitle, $imageName, $activeFlag, $role, $userId);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to update account.');
    }
} catch (Throwable $error) {
    $status = $error instanceof mysqli_sql_exception && $error->getCode() === 1062 ? 'duplicate' : 'save';
    header('Location: ' . publicUrl('admin/accounts.php?error=' . $status));
    exit;
} finally {
    if ($currentStmt instanceof mysqli_stmt) {
        $currentStmt->close();
    }
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

$changes = [];
if ($currentAccount['full_name'] !== $fullName) {
    $changes[] = 'Full name: "' . $currentAccount['full_name'] . '" -> "' . $fullName . '"';
}
if ($currentAccount['username'] !== $username) {
    $changes[] = 'Username: "' . $currentAccount['username'] . '" -> "' . $username . '"';
}
if ($currentAccount['job_title'] !== $jobTitle) {
    $changes[] = 'Job title: "' . $currentAccount['job_title'] . '" -> "' . $jobTitle . '"';
}
if ($currentAccount['image_name'] !== $imageName) {
    $changes[] = 'Image: "' . $currentAccount['image_name'] . '" -> "' . $imageName . '"';
}
if ((int) $currentAccount['active_flag'] !== $activeFlag) {
    $changes[] = 'Status: "' . ($currentAccount['active_flag'] ? 'Active' : 'Inactive') . '" -> "' . ($activeFlag ? 'Active' : 'Inactive') . '"';
}
if ($currentAccount['role'] !== $role) {
    $changes[] = 'Role: "' . $currentAccount['role'] . '" -> "' . $role . '"';
}
if ($password !== '') {
    $changes[] = 'Password changed';
}

$description = 'Updated Account ID #' . $userId . ': ' . ($changes ? "\n" . implode("\n", $changes) : 'No changes');

logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);
header('Location: ' . publicUrl('admin/accounts.php?success=updated'));
exit;
