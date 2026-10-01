<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$redirect = static function (bool $saved): never {
  $query = $saved ? ['edited' => '1'] : ['edit_error' => '1'];
  header('Location: ' . publicUrl('inventory/order_history.php') . '?' . http_build_query($query));
  exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $redirect(false);
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$supplierId = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);
$millerId = filter_var($_POST['ordered_by_user_id'] ?? null, FILTER_VALIDATE_INT);
$orderDate = trim($_POST['order_date'] ?? '');
$orderTime = trim($_POST['order_time'] ?? '');
$expectedArrival = trim($_POST['expected_arrival'] ?? '');
$ingredientIds = $_POST['ingredients_id'] ?? [];
$quantities = $_POST['ordered_quantity_kgs'] ?? [];

$validDate = static function (string $value, string $format): bool {
  $date = DateTime::createFromFormat($format, $value);
  return $date !== false && $date->format($format) === $value;
};

$invalid = !is_int($orderId) || $orderId < 1 || $orderId > 8388607
  || !is_int($supplierId) || $supplierId < 1 || $supplierId > 32767
  || !is_int($millerId) || $millerId < 1 || $millerId > 32767
  || !$validDate($orderDate, 'Y-m-d')
  || !$validDate($orderTime, 'H:i')
  || !$validDate($expectedArrival, 'Y-m-d')
  || !is_array($ingredientIds) || !is_array($quantities)
  || count($ingredientIds) !== count($quantities);

$lines = [];
if (!$invalid) {
  foreach ($ingredientIds as $index => $rawIngredientId) {
    $ingredientId = filter_var($rawIngredientId, FILTER_VALIDATE_INT);
    $rawQuantity = $quantities[$index] ?? '';
    if (!is_int($ingredientId) || $ingredientId < 1 || $ingredientId > 32767
      || !is_numeric($rawQuantity) || (float) $rawQuantity <= 0 || (float) $rawQuantity > 999999.99
      || isset($lines[$ingredientId])) {
      $invalid = true;
      break;
    }
    $lines[$ingredientId] = (float) $rawQuantity;
  }
}

if ($invalid) {
  $redirect(false);
}

$transactionStarted = false;
$changes = [];

try {
  $conn->begin_transaction();
  $transactionStarted = true;

  $orderStmt = $conn->prepare(
    'SELECT supplier_id, ordered_by_user_id, ordered_date_time, expected_arrival FROM orders WHERE order_id = ? FOR UPDATE'
  );
  $orderStmt->bind_param('i', $orderId);
  $orderStmt->execute();
  $currentOrder = $orderStmt->get_result()->fetch_assoc();
  $orderStmt->close();
  if (!$currentOrder) {
    throw new RuntimeException('The selected order was not found.');
  }

  $receivedCheck = $conn->prepare(
    "SELECT COUNT(*) AS received_count FROM orders_ingredients WHERE order_id = ? AND COALESCE(received_flag, b'0') = b'1'"
  );
  $receivedCheck->bind_param('i', $orderId);
  $receivedCheck->execute();
  $receivedCount = (int) $receivedCheck->get_result()->fetch_assoc()['received_count'];
  $receivedCheck->close();
  if ($receivedCount === 0 && count($lines) === 0) {
    throw new RuntimeException('An order must have at least one ingredient line.');
  }

  $supplierCheck = $conn->prepare('SELECT supplier_id FROM suppliers WHERE supplier_id = ?');
  $supplierCheck->bind_param('i', $supplierId);
  $supplierCheck->execute();
  if (!$supplierCheck->get_result()->fetch_assoc()) {
    throw new RuntimeException('The selected supplier is unavailable.');
  }
  $supplierCheck->close();

  $millerCheck = $conn->prepare("SELECT user_id FROM millers WHERE user_id = ? AND active_flag = b'1'");
  $millerCheck->bind_param('i', $millerId);
  $millerCheck->execute();
  if (!$millerCheck->get_result()->fetch_assoc()) {
    throw new RuntimeException('The selected miller is unavailable.');
  }
  $millerCheck->close();

  $orderDateTime = $orderDate . ' ' . $orderTime . ':00';
  $expectedDateTime = $expectedArrival . ' 00:00:00';

  if ((int) $currentOrder['supplier_id'] !== $supplierId) {
    $changes[] = 'supplier #' . $currentOrder['supplier_id'] . ' -> #' . $supplierId;
  }
  if ((int) $currentOrder['ordered_by_user_id'] !== $millerId) {
    $changes[] = 'ordered by #' . $currentOrder['ordered_by_user_id'] . ' -> #' . $millerId;
  }
  if ($currentOrder['ordered_date_time'] !== $orderDateTime) {
    $changes[] = 'order date/time "' . $currentOrder['ordered_date_time'] . '" -> "' . $orderDateTime . '"';
  }
  if (substr((string) $currentOrder['expected_arrival'], 0, 10) !== $expectedArrival) {
    $changes[] = 'expected arrival "' . $currentOrder['expected_arrival'] . '" -> "' . $expectedDateTime . '"';
  }

  $updateOrderStmt = $conn->prepare(
    'UPDATE orders SET supplier_id = ?, ordered_by_user_id = ?, ordered_date_time = ?, expected_arrival = ? WHERE order_id = ?'
  );
  $updateOrderStmt->bind_param('iissi', $supplierId, $millerId, $orderDateTime, $expectedDateTime, $orderId);
  $updateOrderStmt->execute();
  $updateOrderStmt->close();

  $pendingStmt = $conn->prepare(
    "SELECT ingredients_id, ordered_quantity_kgs FROM orders_ingredients
     WHERE order_id = ? AND COALESCE(received_flag, b'0') = b'0' FOR UPDATE"
  );
  $pendingStmt->bind_param('i', $orderId);
  $pendingStmt->execute();
  $previousPending = [];
  foreach ($pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $pendingLine) {
    $previousPending[(int) $pendingLine['ingredients_id']] = (float) $pendingLine['ordered_quantity_kgs'];
  }
  $pendingStmt->close();

  $deleteStmt = $conn->prepare(
    "DELETE FROM orders_ingredients WHERE order_id = ? AND COALESCE(received_flag, b'0') = b'0'"
  );
  $deleteStmt->bind_param('i', $orderId);
  $deleteStmt->execute();
  $deleteStmt->close();

  $ingredientCheck = $conn->prepare("SELECT ingredients_id FROM ingredients WHERE ingredients_id = ? AND active_flag = b'1'");
  $insertStmt = $conn->prepare(
    "INSERT INTO orders_ingredients (order_id, ingredients_id, received_flag, ordered_quantity_kgs) VALUES (?, ?, b'0', ?)"
  );
  foreach ($lines as $ingredientId => $quantity) {
    $ingredientCheck->bind_param('i', $ingredientId);
    $ingredientCheck->execute();
    if (!$ingredientCheck->get_result()->fetch_assoc()) {
      throw new RuntimeException('Ingredient is unavailable.');
    }
    $insertStmt->bind_param('iid', $orderId, $ingredientId, $quantity);
    $insertStmt->execute();

    if (!isset($previousPending[$ingredientId])) {
      $changes[] = 'added ingredient #' . $ingredientId . ' (' . number_format($quantity, 2) . ' kg)';
    } elseif ($previousPending[$ingredientId] !== $quantity) {
      $changes[] = 'ingredient #' . $ingredientId . ' quantity ' . number_format($previousPending[$ingredientId], 2) . ' -> ' . number_format($quantity, 2) . ' kg';
    }
    unset($previousPending[$ingredientId]);
  }
  $ingredientCheck->close();
  $insertStmt->close();

  foreach ($previousPending as $removedIngredientId => $removedQuantity) {
    $changes[] = 'removed ingredient #' . $removedIngredientId . ' (' . number_format($removedQuantity, 2) . ' kg)';
  }

  $conn->commit();
  $transactionStarted = false;
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  $redirect(false);
}

$description = $changes
  ? 'Updated purchase order #' . $orderId . ': ' . implode('; ', $changes)
  : 'Reviewed purchase order #' . $orderId . ': no changes made';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);

$redirect(true);
