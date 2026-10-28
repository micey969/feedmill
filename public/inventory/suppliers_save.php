<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';


$companyNameInput = $_POST['company_name'] ?? null;
$contactPersonInput = $_POST['contact_person'] ?? null;
$countryInput = $_POST['country'] ?? null;
$phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
$emailInput = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
if (!is_string($companyNameInput) || trim($companyNameInput) === '' || !is_string($contactPersonInput) || trim($contactPersonInput) === '' || !is_string($countryInput) || trim($countryInput) === '' || $phone === '' || $emailInput === '') {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=invalid'));
    exit;
}
if (preg_match('/^[a-zA-Z\s]+$/', trim($contactPersonInput)) !== 1) {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=invalid_contact'));
    exit;
}

// Captialize the first letter of each word
$companyName = ucwords(strtolower(trim($companyNameInput)));
$contactPerson = ucwords(strtolower(trim($contactPersonInput)));
$country = trim($countryInput);
$email = strtolower($emailInput);


$stmt = null;
try {
    $countryStmt = $conn->prepare('SELECT country_name FROM countries WHERE country_name = ?');
    $countryStmt->bind_param('s', $country);
    $countryStmt->execute();
    $countryRow = $countryStmt->get_result()->fetch_assoc();
    $countryStmt->close();
    if (!$countryRow) {
        header('Location: ' . publicUrl('inventory/suppliers.php?error=invalid'));
        exit;
    }
    $country = $countryRow['country_name'];

    $stmt = $conn->prepare('INSERT INTO suppliers (company_name, contact_person, country, phone, email) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare supplier creation.');
    }
    $stmt->bind_param('sssss', $companyName, $contactPerson, $country, $phone, $email);
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->errno === 1062 ? 'duplicate' : 'save');
    }
    logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Added Supplier: ' . $companyName);
} catch (Throwable $error) {
    $status = $error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062
        ? 'duplicate'
        : ($error->getMessage() === 'duplicate' ? 'duplicate' : 'save');
    header('Location: ' . publicUrl('inventory/suppliers.php?error=' . $status));
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