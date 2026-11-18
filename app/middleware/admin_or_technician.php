<?php

require_once __DIR__ . '/auth.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'technician'], true)) {
  http_response_code(403);
  require APP_PATH . '/views/errors/access_denied.php';
  exit;
}
