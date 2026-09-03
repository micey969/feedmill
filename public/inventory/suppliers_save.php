<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';


if (
    empty($_POST['company_name']) ||
    empty($_POST['contact_person']) ||
    empty($_POST['country']) ||
    empty($_POST['phone']) ||
    empty($_POST['email'])
) {
    die("All fields are required.");
}

// Captialize the first letter of each word
$companyName = ucwords(strtolower($_POST['company_name']));
$contactPerson = ucwords(strtolower($_POST['contact_person']));
$country = ucwords(strtolower($_POST['country']));

$username = isset($_SESSION['user']) ? $_SESSION['user'] : 'unknown';
$description = 'Added Supplier: ' . $companyName;

logAction($conn, $username, "ADD", $description);

// Prepared statement
// Allows for special characters in details without breaking SQL
$stmt = $conn->prepare("INSERT INTO suppliers 
(company_name, contact_person, country, phone, email) 
VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("ssss", $companyName, $contactPerson, $country, $_POST['phone'], $_POST['email']);


$stmt->execute();


// Refresh page to show the new record in the table
header("Location: suppliers.php");
exit;
?>