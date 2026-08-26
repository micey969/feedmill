<?php

require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['admin_flag'] !== true) {
  http_response_code(403);
  require APP_PATH . '/views/errors/access_denied.php';
  exit;
}