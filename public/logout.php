<?php
require_once __DIR__ . '/../app/init.php';

session_start();

if (isset($_SESSION['user'])) {
  logAction($conn, $_SESSION['user'], "LOGOUT", " ");
}

session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logging out...</title>
  <script>
    // Clear sidebar state from localStorage on logout
    localStorage.removeItem('feedmill-sidebar-collapsed');
  </script>
</head>
<body>
  <script>
    // Redirect to login after clearing storage
    window.location.href = 'login.php';
  </script>
</body>
</html>