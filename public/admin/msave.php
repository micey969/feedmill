<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';


if (
    empty($_POST['full_name']) ||
    empty($_POST['job_title']) 
) {
    die("All fields are required.");
}

// Captialize the first letter of each word in the full name and position
$FullName = ucwords(strtolower($_POST['full_name']));
$Position = ucwords(strtolower($_POST['job_title']));

$username = isset($_SESSION['user']) ? $_SESSION['user'] : 'unknown';
$description = 'Added ' . $FullName . ' - ' . $Position;

logAction($conn, $username, "ADD", $description);

// Prepared statement
// Allows for special characters in details without breaking SQL
$stmt = $conn->prepare("INSERT INTO millers 
(full_name, job_title, active_flag) 
VALUES (?, ?, 1)");

$stmt->bind_param("ss", $FullName, $Position);


$stmt->execute();


// Refresh page to show the new record in the table
header("Location: millers.php");
exit;
?>