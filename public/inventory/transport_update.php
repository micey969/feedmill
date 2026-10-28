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
$transportVersions = $_POST['transport_last_modified'] ?? [];
$redirect = static function (bool $saved, string $reason = '1') use ($orderId): never {
  $query = [$saved ? 'transport_saved' : 'transport_error' => $reason];
  if (is_int($orderId) && $orderId > 0) {
    $query['order_id'] = $orderId;
  }
  header('Location: ' . publicUrl('inventory/receive.php') . '?' . http_build_query($query));
  exit;
};

if (!is_array($transportIds) || !is_array($transportNames) || !is_array($transportVersions) || count($transportIds) !== count($transportNames) || count($transportIds) !== count($transportVersions)) {
  $redirect(false);
}

$updates = [];
$versions = [];
$uniqueNames = [];
foreach ($transportIds as $index => $rawId) {
  $transportId = trim((string) $rawId);
  $transportName = trim((string) ($transportNames[$index] ?? ''));
  $normalizedName = strtolower($transportName);
  if ($transportId === '' || strlen($transportId) > 50 || $transportName === '' || strlen($transportName) > 100) {
    $redirect(false, 'invalid');
  }
  if (isset($uniqueNames[$normalizedName])) {
    $redirect(false, 'duplicate');
  }
  $uniqueNames[$normalizedName] = true;
  $updates[$transportId] = $transportName;
  $versions[$transportId] = is_string($transportVersions[$index] ?? null) ? $transportVersions[$index] : '';
}

$conn->begin_transaction();
$changes = [];
$conflict = false;
try {
  $currentStmt = $conn->prepare('SELECT transport_name, last_modified FROM transport WHERE transport_id = ? FOR UPDATE');
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
    if ((string) $currentTransport['last_modified'] !== $versions[$transportId]) {
      $conflict = true;
      throw new RuntimeException('Transport ' . $transportId . ' was modified by another user.');
    }

    $stmt->bind_param('ss', $transportName, $transportId);
    if (!$stmt->execute()) {
      throw new RuntimeException($stmt->errno === 1062 ? 'duplicate' : 'save');
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
  $status = $conflict
    ? 'conflict'
    : ($error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062
      ? 'duplicate'
      : ($error->getMessage() === 'duplicate' ? 'duplicate' : 'save'));
  $redirect(false, $status);
}

$description = $changes
  ? "Updated transport catalog:\n" . implode("\n", $changes)
  : 'Reviewed transport catalog: no changes made (' . count($updates) . ' transports)';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);

$redirect(true);