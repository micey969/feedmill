<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';


$companyNameInput = $_POST['company_name'] ?? null;
$contactPersonInput = $_POST['contact_person'] ?? null;
$countryInput = $_POST['country'] ?? null;
$phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
if (!is_string($companyNameInput) || trim($companyNameInput) === '' || !is_string($contactPersonInput) || trim($contactPersonInput) === '' || !is_string($countryInput) || trim($countryInput) === '' || $phone === '' || $email === '') {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=invalid'));
    exit;
}

// Captialize the first letter of each word
$companyName = ucwords(strtolower(trim($companyNameInput)));
$contactPerson = ucwords(strtolower(trim($contactPersonInput)));
$country = ucwords(strtolower(trim($countryInput)));

$stmt = null;
try {
    $stmt = $conn->prepare('INSERT INTO suppliers (company_name, contact_person, country, phone, email) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare supplier creation.');
    }
    $stmt->bind_param('sssss', $companyName, $contactPerson, $country, $phone, $email);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to create supplier.');
    }
    logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Added Supplier: ' . $companyName);
} catch (Throwable $error) {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=save'));
    exit;
} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}


// Refresh page to show the new record in the table
header('Location: ' . publicUrl('inventory/suppliers.php?success=created'));
exit;
?>