<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/formulas.php'));
  exit;
}

$name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
if ($name === '') {
  header('Location: ' . publicUrl('products/formulas.php?ingredient_error=invalid'));
  exit;
}

$stmt = null;
try {
  $stmt = $conn->prepare('INSERT INTO ingredients (name, active_flag) VALUES (?, 1)');
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare ingredient insert.');
  }
  $stmt->bind_param('s', $name);
  if (!$stmt->execute()) {
    throw new RuntimeException($stmt->errno === 1062 ? 'duplicate' : 'save');
  }
  $ingredientId = $stmt->insert_id;
} catch (Throwable $error) {
  $status = $error instanceof mysqli_sql_exception && (int) $error->getCode() === 1062 ? 'duplicate' : ($error->getMessage() === 'duplicate' ? 'duplicate' : 'save');
  header('Location: ' . publicUrl('products/formulas.php?ingredient_error=' . $status));
  exit;
} finally {
  if ($stmt instanceof mysqli_stmt) {
    $stmt->close();
  }
}

logAction($conn, $_SESSION['user'] ?? 'unknown', 'ADD', 'Added ingredient #' . $ingredientId . ': ' . $name);
header('Location: ' . publicUrl('products/formulas.php?ingredient_success=created'));
exit;
