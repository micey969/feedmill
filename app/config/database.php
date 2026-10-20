<?php

$envFile = 'C:/xampp/.env';

if (!file_exists($envFile)) {
  die('.env file not found');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
  if (strpos($line, '=') !== false && strpos(ltrim($line), '#') !== 0) {
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
  }
}

$conn = new mysqli();

mysqli_ssl_set(
  $conn,
  null,
  null,
  null,
  null,
  null
);

mysqli_real_connect(
  $conn,
  getenv('DB_HOST'),
  getenv('DB_USER'),
  getenv('DB_PASS'),
  getenv('DB_NAME'),
  null,
  null,
  MYSQLI_CLIENT_SSL
);

if (mysqli_connect_errno()) {
    die('Connection failed: ' . mysqli_connect_error());
}

$conn->set_charset('utf8mb4');
