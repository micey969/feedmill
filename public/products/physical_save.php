<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$stockDate = is_string($_POST['stock_date'] ?? null) ? trim($_POST['stock_date']) : '';
$quantities = $_POST['quantities'] ?? [];

$date = DateTime::createFromFormat('!Y-m-d', $stockDate);
if (!$date || $date->format('Y-m-d') !== $stockDate || !is_array($quantities)) {
  header('Location: ' . publicUrl('products/physical.php?error=invalid'));
  exit;
}

$stockDateTime = $stockDate . ' 00:00:00';
$ingredientStmt = null;
$insertStmt = null;
$transactionStarted = false;
$failureStatus = 'save';

try {
  $ingredientStmt = $conn->prepare('SELECT ingredients_id FROM ingredients WHERE name = ? AND active_flag = 1 LIMIT 1');
  $insertStmt = $conn->prepare('INSERT INTO ingredients_closing_stock (date_time, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');
  if (!$ingredientStmt || !$insertStmt) {
    throw new RuntimeException('Unable to prepare the stock entry.');
  }
  $conn->begin_transaction();
  $transactionStarted = true;

  foreach ($quantities as $ingredientName => $quantity) {
    if (!is_string($ingredientName)) {
      continue;
    }
    if ($quantity === '') {
      $quantity = '0';
    }
    if (!is_scalar($quantity) || !preg_match('/^\d+$/', (string) $quantity) || (int) $quantity > 32767) {
      $failureStatus = 'invalid';
      throw new RuntimeException('Every quantity must be a whole number from 0 to 32767.');
    }

    $ingredientName = trim($ingredientName);
    $quantityKgs = (int) $quantity;
    $ingredientStmt->bind_param('s', $ingredientName);
    if (!$ingredientStmt->execute()) {
      throw new RuntimeException('Unable to find ingredient: ' . $ingredientName);
    }
    $ingredientId = $ingredientStmt->get_result()->fetch_assoc()['ingredients_id'] ?? null;
    if ($ingredientId === null) {
      $failureStatus = 'invalid';
      throw new RuntimeException('Unknown ingredient: ' . $ingredientName);
    }

    $insertStmt->bind_param('sii', $stockDateTime, $ingredientId, $quantityKgs);
    if (!$insertStmt->execute()) {
      throw new RuntimeException('Unable to save ingredient: ' . $ingredientName);
    }
  }

  $conn->commit();
  $transactionStarted = false;
  logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Saved physical stock for ' . $stockDate);
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  header('Location: ' . publicUrl('products/physical.php?error=' . $failureStatus));
  exit;
} finally {
  if ($ingredientStmt instanceof mysqli_stmt) {
    $ingredientStmt->close();
  }
  if ($insertStmt instanceof mysqli_stmt) {
    $insertStmt->close();
  }
}

header('Location: ' . publicUrl('products/physical.php?success=1'));
exit;
