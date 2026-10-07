<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$supplierId = filter_input(INPUT_POST, 'supplier_id', FILTER_VALIDATE_INT);
$companyName = is_string($_POST['company_name'] ?? null) ? trim($_POST['company_name']) : '';
$contactPerson = is_string($_POST['contact_person'] ?? null) ? trim($_POST['contact_person']) : '';
$country = is_string($_POST['country'] ?? null) ? trim($_POST['country']) : '';
$phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

if (!$supplierId || $companyName === '' || $contactPerson === '' || $country === '' || $phone === '' || $email === '') {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=invalid'));
    exit;
}

$companyName = ucwords(strtolower($companyName));
$contactPerson = ucwords(strtolower($contactPerson));
$country = ucwords(strtolower($country));

$currentStmt = null;
$stmt = null;
try {
    $currentStmt = $conn->prepare('SELECT company_name, contact_person, country, phone, email FROM suppliers WHERE supplier_id = ?');
    if (!$currentStmt) {
        throw new RuntimeException('Unable to load supplier.');
    }
    $currentStmt->bind_param('i', $supplierId);
    $currentStmt->execute();
    $currentSupplier = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();
    $currentStmt = null;

    if (!$currentSupplier) {
        header('Location: ' . publicUrl('inventory/suppliers.php?error=not_found'));
        exit;
    }

    $stmt = $conn->prepare('UPDATE suppliers SET company_name = ?, contact_person = ?, country = ?, phone = ?, email = ? WHERE supplier_id = ?');
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare supplier update.');
    }
    $stmt->bind_param('sssssi', $companyName, $contactPerson, $country, $phone, $email, $supplierId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Unable to update supplier.');
    }
} catch (Throwable $error) {
    header('Location: ' . publicUrl('inventory/suppliers.php?error=save'));
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

$description = 'Updated Supplier ID #' . $supplierId . ': ' . ($changes ? "\n" . implode("\n", $changes) : 'No changes');
logAction($conn, $username ?? 'unknown', 'UPDATE', $description);

header('Location: ' . publicUrl('inventory/suppliers.php?success=updated'));
exit;