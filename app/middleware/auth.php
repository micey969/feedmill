<?php

require_once __DIR__ . '/../init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$timeout = 600;

if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

if (isset($_SESSION['last_activity'])) {
  $inactiveTime = time() - $_SESSION['last_activity'];

  if ($inactiveTime > $timeout) {
    logAction($conn, $_SESSION['user'], 'TIMEOUT', 'Session timed out after 10 minutes of inactivity');
    session_unset();
    session_destroy();

    header('Location: login.php?timeout=1');
    exit;
  }
}

$_SESSION['last_activity'] = time();
