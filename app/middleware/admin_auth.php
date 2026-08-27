<?php

require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user']) || (int) ($_SESSION['admin_flag'] ?? 0) !== 1) {
  http_response_code(403);
  require APP_PATH . '/views/errors/access_denied.php';
  exit;
}