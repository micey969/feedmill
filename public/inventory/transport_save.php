<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('inventory/receive.php'));
  exit;
}

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$transportName = trim($_POST['transport_name'] ?? '');
$returnToReceive = static function (bool $saved, string $transportId = '', string $error = 'save') use ($orderId): never {
  $query = [$saved ? 'transport_saved' : 'transport_error' => $saved ? '1' : $error];
  if (is_int($orderId) && $orderId > 0) {
    $query['order_id'] = $orderId;
  }
  if ($saved && $transportId !== '') {
    $query['transport_id'] = $transportId;
  }
  header('Location: ' . publicUrl('inventory/receive.php') . '?' . http_build_query($query));
  exit;
};

if ($transportName === '' || strlen($transportName) > 100) {
  $returnToReceive(false, '', 'invalid');
}

$slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $transportName), '-'));
$transportId = substr($slug !== '' ? $slug : 'transport', 0, 37) . '-' . substr(hash('sha256', strtolower($transportName)), 0, 12);
$stmt = $conn->prepare('INSERT INTO transport (transport_id, transport_name) VALUES (?, ?)');
if (!$stmt) {
  $returnToReceive(false);
}

try {
  $stmt->bind_param('ss', $transportId, $transportName);
  if (!$stmt->execute()) {
    throw new RuntimeException($stmt->errno === 1062 ? 'duplicate' : 'save');
  }
} catch (Throwable $error) {
  $stmt->close();
  $status = $error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062
    ? 'duplicate'
    : ($error->getMessage() === 'duplicate' ? 'duplicate' : 'save');
  $returnToReceive(false, '', $status);
}
$stmt->close();

logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Added transport ' . $transportId . ': ' . $transportName);

$returnToReceive(true, $transportId);