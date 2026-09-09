<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$stockDate = trim($_POST['stock_date'] ?? '');
$quantities = $_POST['quantities'] ?? [];

$date = DateTime::createFromFormat('!Y-m-d', $stockDate);
if (!$date || $date->format('Y-m-d') !== $stockDate || !is_array($quantities)) {
  http_response_code(400);
  exit('Invalid stock entry.');
}

$stockDateTime = $stockDate . ' 00:00:00';
$ingredientStmt = $conn->prepare('SELECT ingredients_id FROM ingredients WHERE name = ? AND active_flag = 1 LIMIT 1');
$insertStmt = $conn->prepare('INSERT INTO ingredients_closing_stock (date_time, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');

if (!$ingredientStmt || !$insertStmt) {
  http_response_code(500);
  exit('Unable to prepare the stock entry.');
}

try {
  $conn->begin_transaction();

  foreach ($quantities as $ingredientName => $quantity) {
    if (!is_string($ingredientName)) {
      continue;
    }
    if ($quantity === '') {
      $quantity = '0';
    }
    if (!is_scalar($quantity) || !preg_match('/^\d+$/', (string) $quantity) || (int) $quantity > 32767) {
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
      throw new RuntimeException('Unknown ingredient: ' . $ingredientName);
    }

    $insertStmt->bind_param('sii', $stockDateTime, $ingredientId, $quantityKgs);
    if (!$insertStmt->execute()) {
      throw new RuntimeException('Unable to save ingredient: ' . $ingredientName);
    }
  }

  $conn->commit();
  logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Saved physical stock for ' . $stockDate);
} catch (Throwable $error) {
  $conn->rollback();
  http_response_code(500);
  exit($error->getMessage());
} finally {
  $ingredientStmt->close();
  $insertStmt->close();
}

header('Location: physical.php?success=1');
exit;
