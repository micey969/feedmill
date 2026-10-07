<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';


$fullNameInput = $_POST['full_name'] ?? null;
$jobTitleInput = $_POST['job_title'] ?? null;
if (!is_string($fullNameInput) || trim($fullNameInput) === '' || !is_string($jobTitleInput) || !in_array($jobTitleInput, ['Mill Operator', 'Assistant Senior Miller', 'Shift Supervisor', 'Assistant Mill Supervisor'], true)) {
    header('Location: ' . publicUrl('admin/millers.php?error=invalid'));
    exit;
}

// Captialize the first letter of each word in the full name and position
$FullName = ucwords(strtolower(trim($fullNameInput)));
$Position = ucwords(strtolower($jobTitleInput));

$stmt = null;
try {
    $stmt = $conn->prepare('INSERT INTO millers (full_name, job_title, active_flag) VALUES (?, ?, 1)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare miller creation.');
    }
    $stmt->bind_param('ss', $FullName, $Position);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to create miller record.');
    }

    $username = $_SESSION['user'] ?? 'unknown';
    logAction($conn, $username, 'ADD', 'Added ' . $FullName . ' - ' . $Position);
} catch (Throwable $error) {
    header('Location: ' . publicUrl('admin/millers.php?error=save'));
    exit;
} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}


// Refresh page to show the new record in the table
header('Location: ' . publicUrl('admin/millers.php?success=created'));
exit;
?>