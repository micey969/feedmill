<?php
require_once __DIR__ . '/../app/init.php';

session_start();

if (isset($_SESSION['user'])) {
  logAction($conn, $_SESSION['user'], "LOGOUT", "User logged out: " . $_SESSION['user']);
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>