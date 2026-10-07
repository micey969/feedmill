<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$millerId = filter_var($_POST['miller_id'] ?? null, FILTER_VALIDATE_INT);
$transportId = trim($_POST['transport_id'] ?? '');
$invoiceNumber = trim($_POST['invoice_number'] ?? '');
$arrivalDate = trim($_POST['arrival_date'] ?? '');
$arrivalTime = trim($_POST['arrival_time'] ?? '');
$ingredientIds = $_POST['receive_ingredients'] ?? [];
$quantities = $_POST['received_quantities'] ?? [];
$redirect = static function (string $status) use ($orderId): never {
  $query = [];
  if (is_int($orderId) && $orderId > 0) {
    $query['order_id'] = $orderId;
  }
  $query[$status] = '1';
  header('Location: ' . publicUrl('inventory/receive.php') . '?' . http_build_query($query));
  exit;
};
$validDate = static function (string $value, string $format): bool {
  $date = DateTime::createFromFormat($format, $value);
  return $date !== false && $date->format($format) === $value;
};

if (!is_int($orderId) || $orderId < 1 || $orderId > 8388607
  || !is_int($millerId) || $millerId < 1 || $millerId > 32767
  || $transportId === '' || strlen($transportId) > 50
  || strlen($invoiceNumber) > 50
  || !$validDate($arrivalDate, 'Y-m-d') || !$validDate($arrivalTime, 'H:i')
  || !is_array($ingredientIds) || !is_array($quantities) || !$ingredientIds) {
  $redirect('error');
}

$selectedLines = [];
foreach ($ingredientIds as $rawIngredientId) {
  $ingredientId = filter_var($rawIngredientId, FILTER_VALIDATE_INT);
  $rawQuantity = is_int($ingredientId) ? ($quantities[$ingredientId] ?? null) : null;
  if (!is_int($ingredientId) || $ingredientId < 1 || $ingredientId > 32767
    || array_key_exists($ingredientId, $selectedLines)
    || !is_numeric($rawQuantity) || !is_finite((float) $rawQuantity)
    || (float) $rawQuantity < 0 || (float) $rawQuantity > 999999.99) {
    $redirect('error');
  }
  $selectedLines[$ingredientId] = (float) $rawQuantity;
}

$transactionStarted = false;
try {
  $conn->begin_transaction();
  $transactionStarted = true;

  $pendingStmt = $conn->prepare(
    "SELECT ingredients_id, received_by_user_id, received_quantity_kgs, received_date_time FROM orders_ingredients
      WHERE order_id = ? AND COALESCE(received_flag, b'0') = b'0'
      FOR UPDATE"
  );
  $pendingStmt->bind_param('i', $orderId);
  $pendingStmt->execute();
  $pendingLines = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $pendingStmt->close();
  $pendingById = [];
  foreach ($pendingLines as $pendingLine) {
    $pendingById[(int) $pendingLine['ingredients_id']] = $pendingLine;
  }
  if (!$pendingById) {
    throw new RuntimeException('This order has no pending ingredient lines.');
  }

  foreach ($selectedLines as $ingredientId => $quantity) {
    if (!isset($pendingById[$ingredientId])) {
      throw new RuntimeException('An ingredient line is no longer pending.');
    }
  }

  $millerStmt = $conn->prepare("SELECT user_id FROM millers WHERE user_id = ? AND active_flag = b'1'");
  $millerStmt->bind_param('i', $millerId);
  $millerStmt->execute();
  if (!$millerStmt->get_result()->fetch_assoc()) {
    throw new RuntimeException('The selected receiving inspector is unavailable.');
  }
  $millerStmt->close();

  $transportStmt = $conn->prepare('SELECT transport_id FROM transport WHERE transport_id = ?');
  $transportStmt->bind_param('s', $transportId);
  $transportStmt->execute();
  if (!$transportStmt->get_result()->fetch_assoc()) {
    throw new RuntimeException('The selected transport is unavailable.');
  }
  $transportStmt->close();

  $orderLookupStmt = $conn->prepare(
    'SELECT invoice_number, received_transport_id FROM orders WHERE order_id = ? FOR UPDATE'
  );
  $orderLookupStmt->bind_param('i', $orderId);
  $orderLookupStmt->execute();
  $currentOrder = $orderLookupStmt->get_result()->fetch_assoc();
  $orderLookupStmt->close();
  if (!$currentOrder) {
    throw new RuntimeException('The selected order was not found.');
  }

  $receivedDateTime = $arrivalDate . ' ' . $arrivalTime . ':00';
  $changes = [];
  if ($invoiceNumber !== '' && $currentOrder['invoice_number'] !== $invoiceNumber) {
    $changes[] = 'invoice number "' . ($currentOrder['invoice_number'] ?? 'not set') . '" -> "' . $invoiceNumber . '"';
  }
  if ($currentOrder['received_transport_id'] !== $transportId) {
    $changes[] = 'transport "' . ($currentOrder['received_transport_id'] ?? 'not set') . '" -> "' . $transportId . '"';
  }

  $orderStmt = $conn->prepare(
    "UPDATE orders
     SET invoice_number = COALESCE(NULLIF(?, ''), invoice_number), received_transport_id = ?
     WHERE order_id = ?"
  );
  $orderStmt->bind_param('ssi', $invoiceNumber, $transportId, $orderId);
  $orderStmt->execute();
  if ($orderStmt->affected_rows < 0) {
    throw new RuntimeException('Unable to update order receipt details.');
  }
  $orderStmt->close();

  $lineStmt = $conn->prepare(
    "UPDATE orders_ingredients
     SET received_by_user_id = ?, received_flag = b'1', received_quantity_kgs = ?, received_date_time = ?
     WHERE order_id = ? AND ingredients_id = ? AND (received_flag IS NULL OR received_flag <> b'1')"
  );
  foreach ($selectedLines as $ingredientId => $quantity) {
    $previousLine = $pendingById[$ingredientId];
    $previousQuantity = $previousLine['received_quantity_kgs'] === null
      ? 'not set'
      : number_format((float) $previousLine['received_quantity_kgs'], 2) . ' kg';
    $lineChanges = [
      'Status Pending -> Received',
      'Received quantity ' . $previousQuantity . ' -> ' . number_format($quantity, 2) . ' kg',
    ];
    if ((string) ($previousLine['received_by_user_id'] ?? '') !== (string) $millerId) {
      $lineChanges[] = 'Miller "' . ($previousLine['received_by_user_id'] ?? 'not set') . '" -> "' . $millerId . '"';
    }
    if ((string) ($previousLine['received_date_time'] ?? '') !== $receivedDateTime) {
      $lineChanges[] = 'Received at "' . ($previousLine['received_date_time'] ?? 'not set') . '" -> "' . $receivedDateTime . '"';
    }
    $changes[] = 'Ingredient #' . $ingredientId . ': ' . implode("\n  ", $lineChanges);

    $lineStmt->bind_param('idsii', $millerId, $quantity, $receivedDateTime, $orderId, $ingredientId);
    $lineStmt->execute();
    if ($lineStmt->affected_rows !== 1) {
      throw new RuntimeException('An ingredient line could not be marked received.');
    }
  }
  $lineStmt->close();

  $conn->commit();
  $transactionStarted = false;
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  $redirect('error');
}

$description = 'Received ingredients for order #' . $orderId . ":\n" . implode("\n", $changes);
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);

$redirect('success');