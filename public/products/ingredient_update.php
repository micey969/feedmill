<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/formulas.php'));
  exit;
}

$ids = $_POST['ids'] ?? [];
$names = $_POST['names'] ?? [];
$activeFlags = $_POST['active_flags'] ?? [];
if (!is_array($ids) || !is_array($names) || !is_array($activeFlags) || count($ids) !== count($names) || count($ids) !== count($activeFlags)) {
  header('Location: ' . publicUrl('products/formulas.php?ingredient_error=invalid'));
  exit;
}

$stmt = null;
$currentStmt = null;
$changes = [];
$transactionStarted = false;
try {
  $stmt = $conn->prepare('UPDATE ingredients SET name = ?, active_flag = ? WHERE ingredients_id = ?');
  $currentStmt = $conn->prepare('SELECT name, active_flag FROM ingredients WHERE ingredients_id = ?');
  if (!$stmt || !$currentStmt) {
    throw new RuntimeException('Unable to prepare ingredient changes.');
  }
  $conn->begin_transaction();
  $transactionStarted = true;
  foreach ($ids as $index => $rawId) {
    $ingredientId = filter_var($rawId, FILTER_VALIDATE_INT);
    $name = is_string($names[$index] ?? null) ? trim($names[$index]) : '';
    $activeFlag = filter_var($activeFlags[$index] ?? null, FILTER_VALIDATE_INT);
    if ($ingredientId === false || $ingredientId <= 0 || $name === '' || !in_array($activeFlag, [0, 1], true)) {
      throw new RuntimeException('Invalid ingredient data.');
    }

    $currentStmt->bind_param('i', $ingredientId);
    if (!$currentStmt->execute()) {
      throw new RuntimeException('Unable to read current ingredient details.');
    }
    $currentIngredient = $currentStmt->get_result()->fetch_assoc();
    if (!$currentIngredient) {
      throw new RuntimeException('Ingredient #' . $ingredientId . ' was not found.');
    }

    $ingredientChanges = [];
    if ($currentIngredient['name'] !== $name) {
      $ingredientChanges[] = 'name "' . $currentIngredient['name'] . '" -> "' . $name . '"';
    }
    if ((int) $currentIngredient['active_flag'] !== $activeFlag) {
      $oldStatus = (int) $currentIngredient['active_flag'] === 1 ? 'Active' : 'Inactive';
      $newStatus = $activeFlag === 1 ? 'Active' : 'Inactive';
      $ingredientChanges[] = 'status ' . $oldStatus . ' -> ' . $newStatus;
    }
    if ($ingredientChanges) {
      $changes[] = '#' . $ingredientId . ' (' . $currentIngredient['name'] . '): ' . implode("\n  ", $ingredientChanges);
    }

    $stmt->bind_param('sii', $name, $activeFlag, $ingredientId);
    if (!$stmt->execute()) {
      throw new RuntimeException($stmt->errno === 1062 ? 'An ingredient with that name already exists.' : 'Unable to update ingredient.');
    }
  }
  $conn->commit();
  $transactionStarted = false;
} catch (Throwable $exception) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  if ($currentStmt instanceof mysqli_stmt) {
    $currentStmt->close();
  }
  if ($stmt instanceof mysqli_stmt) {
    $stmt->close();
  }
  $status = $exception instanceof mysqli_sql_exception && (int) $exception->getCode() === 1062 ? 'duplicate' : ($exception->getMessage() === 'An ingredient with that name already exists.' ? 'duplicate' : 'save');
  header('Location: ' . publicUrl('products/formulas.php?ingredient_error=' . $status));
  exit;
}
$currentStmt->close();
$stmt->close();

$description = $changes
  ? "Updated ingredient catalog:\n" . implode("\n", $changes)
  : 'Reviewed ingredient catalog: no changes made (' . count($ids) . ' ingredients)';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);
header('Location: ' . publicUrl('products/formulas.php?ingredient_success=updated'));
exit;
