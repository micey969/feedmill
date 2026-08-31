<?php
require_once __DIR__ . '/../app/init.php';

session_start();

if (isset($_SESSION['user'])) {
  logAction($conn, $_SESSION['user'], "LOGOUT", " ");
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>