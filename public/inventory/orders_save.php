<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

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

$invalid = !is_int($supplierId) || $supplierId < 1 || $supplierId > 32767
  || !is_int($millerId) || $millerId < 1 || $millerId > 32767
  || !$validDate($orderDate, 'Y-m-d')
  || !$validDate($orderTime, 'H:i')
  || !$validDate($expectedArrival, 'Y-m-d')
  || !is_array($ingredientIds) || !is_array($quantities)
  || count($ingredientIds) === 0 || count($ingredientIds) !== count($quantities);

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
  header('Location: ' . publicUrl('inventory/orders.php?error=invalid'));
  exit;
}

$lockAcquired = false;
$transactionStarted = false;
$orderId = 0;
$saveFailed = false;

try {
  $lockResult = $conn->query("SELECT GET_LOCK('feedmill_orders_order_id', 10) AS acquired")->fetch_assoc();
  if ((int) ($lockResult['acquired'] ?? 0) !== 1) {
    throw new RuntimeException('Unable to reserve an order number.');
  }
  $lockAcquired = true;

  $conn->begin_transaction();
  $transactionStarted = true;
  $nextIdResult = $conn->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS next_order_id FROM orders');
  $orderId = (int) $nextIdResult->fetch_assoc()['next_order_id'];
  if ($orderId < 1 || $orderId > 8388607) {
    throw new RuntimeException('Order number is outside the supported range.');
  }

  $orderDateTime = $orderDate . ' ' . $orderTime . ':00';
  $expectedDateTime = $expectedArrival . ' 00:00:00';
  $orderStmt = $conn->prepare(
    'INSERT INTO orders (order_id, supplier_id, ordered_by_user_id, ordered_date_time, expected_arrival) VALUES (?, ?, ?, ?, ?)'
  );
  $orderStmt->bind_param('iiiss', $orderId, $supplierId, $millerId, $orderDateTime, $expectedDateTime);
  $orderStmt->execute();
  $orderStmt->close();

  $ingredientCheck = $conn->prepare("SELECT ingredients_id FROM ingredients WHERE ingredients_id = ? AND active_flag = b'1'");
  $lineStmt = $conn->prepare("INSERT INTO orders_ingredients (order_id, ingredients_id, received_flag, ordered_quantity_kgs) VALUES (?, ?, b'0', ?)");
  foreach ($lines as $ingredientId => $quantity) {
    $ingredientCheck->bind_param('i', $ingredientId);
    $ingredientCheck->execute();
    if (!$ingredientCheck->get_result()->fetch_assoc()) {
      throw new RuntimeException('Ingredient is unavailable.');
    }
    $lineStmt->bind_param('iid', $orderId, $ingredientId, $quantity);
    $lineStmt->execute();
  }
  $ingredientCheck->close();
  $lineStmt->close();

  $conn->commit();
  $transactionStarted = false;
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  $saveFailed = true;
} finally {
  if ($lockAcquired) {
    $conn->query("SELECT RELEASE_LOCK('feedmill_orders_order_id')");
  }
}

if ($saveFailed) {
  header('Location: ' . publicUrl('inventory/orders.php?error=save'));
  exit;
}

logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Created purchase order #' . $orderId . ' with ' . count($lines) . ' ingredient lines');

header('Location: ' . publicUrl('inventory/orders.php?success=1'));
exit;