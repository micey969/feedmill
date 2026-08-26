<?php

function logAction(mysqli $conn, string $username, string $action, string $details = ''): void
{
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  $stmt = $conn->prepare(
    'INSERT INTO audit_log (username, action_type, time_stamp, ip_address, details)
    VALUES (?, ?, NOW(), ?, ?)'
  );

  if (!$stmt) {
    return;
  }

  $stmt->bind_param('ssss', $username, $action, $ip, $details);
  $stmt->execute();
  $stmt->close();
}
