<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('inventory/receive.php'));
  exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$transportIds = $_POST['transport_ids'] ?? [];
$transportNames = $_POST['transport_names'] ?? [];
$redirect = static function (bool $saved) use ($orderId): never {
  $query = [$saved ? 'transport_saved' : 'transport_error' => '1'];
  if (is_int($orderId) && $orderId > 0) {
    $query['order_id'] = $orderId;
  }
  header('Location: ' . publicUrl('inventory/receive.php') . '?' . http_build_query($query));
  exit;
};

if (!is_array($transportIds) || !is_array($transportNames) || count($transportIds) !== count($transportNames)) {
  $redirect(false);
}

$updates = [];
$uniqueNames = [];
foreach ($transportIds as $index => $rawId) {
  $transportId = trim((string) $rawId);
  $transportName = trim((string) ($transportNames[$index] ?? ''));
  $normalizedName = strtolower($transportName);
  if ($transportId === '' || strlen($transportId) > 50 || $transportName === '' || strlen($transportName) > 100
    || isset($uniqueNames[$normalizedName])) {
    $redirect(false);
  }
  $uniqueNames[$normalizedName] = true;
  $updates[$transportId] = $transportName;
}

$conn->begin_transaction();
$changes = [];
try {
  $currentStmt = $conn->prepare('SELECT transport_name FROM transport WHERE transport_id = ? FOR UPDATE');
  $stmt = $conn->prepare('UPDATE transport SET transport_name = ? WHERE transport_id = ?');
  if (!$currentStmt || !$stmt) {
    throw new RuntimeException('Unable to prepare transport changes.');
  }
  foreach ($updates as $transportId => $transportName) {
    $currentStmt->bind_param('s', $transportId);
    if (!$currentStmt->execute()) {
      throw new RuntimeException('Unable to read current transport details.');
    }
    $currentTransport = $currentStmt->get_result()->fetch_assoc();
    if (!$currentTransport) {
      throw new RuntimeException('Transport ' . $transportId . ' was not found.');
    }
    if ($currentTransport['transport_name'] !== $transportName) {
      $changes[] = $transportId . ': name "' . $currentTransport['transport_name'] . '" -> "' . $transportName . '"';
    }

    $stmt->bind_param('ss', $transportName, $transportId);
    if (!$stmt->execute()) {
      throw new RuntimeException('Unable to save transport changes.');
    }
  }
  $currentStmt->close();
  $stmt->close();
  $conn->commit();
} catch (Throwable $error) {
  $conn->rollback();
  if (isset($currentStmt) && $currentStmt instanceof mysqli_stmt) {
    $currentStmt->close();
  }
  if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
  }
  $redirect(false);
}

$description = $changes
  ? 'Updated transport catalog: ' . implode('; ', $changes)
  : 'Reviewed transport catalog: no changes made (' . count($updates) . ' transports)';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);

$redirect(true);