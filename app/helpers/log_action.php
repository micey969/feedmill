<?php

function logAction(mysqli $conn, string $username, string $action, string $details = ''): void
{
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

  $stmt = $conn->prepare(
    'INSERT INTO log (username, action, action_time, ip_address, details)
    VALUES (?, ?, NOW(), ?, ?)'
  );

  if (!$stmt) {
    return;
  }

  $stmt->bind_param('ssss', $username, $action, $ip, $details);
  $stmt->execute();
  $stmt->close();
}
