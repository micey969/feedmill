<?php
require_once __DIR__ . '/../../init.php';

session_start();


if (isset($_GET['timeout'])) {
    $timeout_msg = "⏳ Session expired. Please log in again.";
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Gets user from database
    $sql = "SELECT * FROM accounts WHERE username='$username' AND password='$password' AND active_flag = 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        session_regenerate_id(true);
        
        $_SESSION['user'] = $user['username'];
        $_SESSION['full_name'] = isset($user['full_name']) ? $user['full_name'] : '';
        $_SESSION['job_title'] = isset($user['job_title']) ? $user['job_title'] : '';
        $_SESSION['image_name'] = isset($user['image_name']) ? $user['image_name'] : '';
        $_SESSION['admin_flag'] = isset($user['admin_flag']) && (int) $user['admin_flag'] === 1;
        $_SESSION['last_activity'] = time();
        logAction($conn, $_SESSION['user'], "LOGIN", " ");

        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid login";
    }
}
?>