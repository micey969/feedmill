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

$conn = new mysqli(
  getenv('DB_HOST'),
  getenv('DB_USER'),
  getenv('DB_PASS'),
  getenv('DB_NAME')
);

if ($conn->connect_error) {
  die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
