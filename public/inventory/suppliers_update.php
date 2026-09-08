<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
$companyName = trim($_POST['company_name'] ?? '');
$contactPerson = trim($_POST['contact_person'] ?? '');
$country = trim($_POST['country'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

if (!$supplierId || $companyName === '' || $contactPerson === '' || $country === '' || $phone === '' || $email === '') {
    die('All fields are required.');
}

$companyName = ucwords(strtolower($companyName));
$contactPerson = ucwords(strtolower($contactPerson));
$country = ucwords(strtolower($country));

$currentStmt = $conn->prepare(
    'SELECT company_name, contact_person, country, phone, email FROM suppliers WHERE supplier_id = ?'
);

if (!$currentStmt) {
    die('Prepare failed: ' . $conn->error);
}

$currentStmt->bind_param('i', $supplierId);
$currentStmt->execute();
$currentResult = $currentStmt->get_result();
$currentSupplier = $currentResult->fetch_assoc();
$currentStmt->close();

if (!$currentSupplier) {
    die('Supplier record not found.');
}

$stmt = $conn->prepare(
    'UPDATE suppliers SET company_name = ?, contact_person = ?, country = ?, phone = ?, email = ? WHERE supplier_id = ?'
);

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('sssssi', $companyName, $contactPerson, $country, $phone, $email, $supplierId);

if (!$stmt->execute()) {
    die('Update failed: ' . $stmt->error);
}

$username = $_SESSION['user'] ?? 'unknown';
$changes = [];

if ($currentSupplier['company_name'] !== $companyName) {
    $changes[] = 'Company name: "' . $currentSupplier['company_name'] . '" -> "' . $companyName . '"';
}

if ($currentSupplier['contact_person'] !== $contactPerson) {
    $changes[] = 'Contact person: "' . $currentSupplier['contact_person'] . '" -> "' . $contactPerson . '"';
}

if ($currentSupplier['country'] !== $country) {
    $changes[] = 'Country: "' . $currentSupplier['country'] . '" -> "' . $country . '"';
}

if ($currentSupplier['phone'] !== $phone) {
    $changes[] = 'Phone: "' . $currentSupplier['phone'] . '" -> "' . $phone . '"';
}

if ($currentSupplier['email'] !== $email) {
    $changes[] = 'Email: "' . $currentSupplier['email'] . '" -> "' . $email . '"';
}

$description = 'Updated Supplier ID #' . $supplierId . ': ' . ($changes ? implode('; ', $changes) : 'No changes');
logAction($conn, $username ?? 'unknown', 'UPDATE', $description);

$stmt->close();
header('Location: suppliers.php');
exit;