<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$entryDate = is_string($_POST['entry_date'] ?? null) ? trim($_POST['entry_date']) : '';
$ingredientId = filter_var($_POST['ingredients_id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity_kgs'] ?? null, FILTER_VALIDATE_INT);
$saleOption = $_POST['sale_option'] ?? 'local';

$date = DateTime::createFromFormat('!Y-m-d', $entryDate);
if (!$date || $date->format('Y-m-d') !== $entryDate || $ingredientId === false || $ingredientId <= 0 || $quantity === false || $quantity < 0 || $quantity > 32767 || !in_array($saleOption, ['local', 'overseas'], true)) {
  header('Location: ' . publicUrl('production/items.php?error=invalid'));
  exit;
}

$dateTime = $entryDate . ' ' . date('H:i:s');
$overseasFlag = $saleOption === 'overseas' ? 1 : 0;
$ingredientStmt = null;
$insertStmt = null;

try {
  $ingredientStmt = $conn->prepare('SELECT ingredients_id FROM v_ingredients_sold_separately WHERE ingredients_id = ? LIMIT 1');
  $insertStmt = $conn->prepare('INSERT INTO ingredients_sold_separately (ingredients_id, date_time, quantity_kgs, overseas_flag) VALUES (?, ?, ?, ?)');
  if (!$ingredientStmt || !$insertStmt) {
    throw new RuntimeException('Unable to prepare the sale entry.');
  }
  $ingredientStmt->bind_param('i', $ingredientId);
  $ingredientStmt->execute();
  if (!$ingredientStmt->get_result()->fetch_assoc()) {
    throw new RuntimeException('Unknown ingredient.');
  }

  $insertStmt->bind_param('isii', $ingredientId, $dateTime, $quantity, $overseasFlag);
  if (!$insertStmt->execute()) {
    throw new RuntimeException('Unable to save the sale entry.');
  }

  logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Sold ingredient #' . $ingredientId . ' (' . $quantity . ' kg)');
} catch (Throwable $error) {
  header('Location: ' . publicUrl('production/items.php?error=save'));
  exit;
} finally {
  if ($ingredientStmt instanceof mysqli_stmt) {
    $ingredientStmt->close();
  }
  if ($insertStmt instanceof mysqli_stmt) {
    $insertStmt->close();
  }
}

header('Location: ' . publicUrl('production/items.php?success=1'));
exit;
