<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/formulas.php'));
  exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
if ($name === '') {
  die('Ingredient name is required.');
}

$stmt = $conn->prepare('INSERT INTO ingredients (name, active_flag) VALUES (?, 1)');
if (!$stmt) {
  die('Unable to prepare ingredient insert: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('s', $name);
if (!$stmt->execute()) {
  $message = $stmt->errno === 1062 ? 'An ingredient with that name already exists.' : 'Unable to add ingredient.';
  $stmt->close();
  die($message);
}
$ingredientId = $stmt->insert_id;
$stmt->close();

logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Added ingredient #' . $ingredientId . ': ' . $name);
header('Location: ' . publicUrl('products/formulas.php'));
exit;
