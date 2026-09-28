<?php

require_once __DIR__ . '/../init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$timeout = 600;

if (!isset($_SESSION['user'])) {
  header('Location: ' . publicUrl('login.php'));
  exit;
}

if (isset($_SESSION['last_activity'])) {
  $inactiveTime = time() - $_SESSION['last_activity'];

  if ($inactiveTime > $timeout) {
    logAction($conn, $_SESSION['user'], 'TIMEOUT', 'Session timed out after 10 minutes of inactivity');
    session_unset();
    session_destroy();

    header('Location: ' . publicUrl('login.php?timeout=1'));
    exit;
  }
}

$role = $_SESSION['role'] ?? 'user';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$currentPage = ltrim(substr($scriptName, strlen(PUBLIC_URL)), '/');
$userAllowedPages = [
  'index.php',
  'production/mixing.php',
  'production/mixing_save.php',
  'reports/materials.php',
  'reports/sold.php',
  'reports/feeds.php',
  'reports/summary.php',
  'reports/print_log.php',
];

if ($role === 'user' && !in_array($currentPage, $userAllowedPages, true)) {
  http_response_code(403);
  require APP_PATH . '/views/errors/access_denied.php';
  exit;
}

$_SESSION['last_activity'] = time();
