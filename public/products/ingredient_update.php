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
  die('Invalid ingredient data.');
}

$stmt = $conn->prepare('UPDATE ingredients SET name = ?, active_flag = ? WHERE ingredients_id = ?');
if (!$stmt) {
  die('Unable to prepare ingredient update: ' . htmlspecialchars($conn->error));
}
$currentStmt = $conn->prepare('SELECT name, active_flag FROM ingredients WHERE ingredients_id = ?');
if (!$currentStmt) {
  $stmt->close();
  die('Unable to prepare ingredient lookup: ' . htmlspecialchars($conn->error));
}

$conn->begin_transaction();
$changes = [];
try {
  foreach ($ids as $index => $rawId) {
    $ingredientId = filter_var($rawId, FILTER_VALIDATE_INT);
    $name = trim((string) $names[$index]);
    $activeFlag = filter_var($activeFlags[$index], FILTER_VALIDATE_INT);
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
      $changes[] = '#' . $ingredientId . ' (' . $currentIngredient['name'] . '): ' . implode(', ', $ingredientChanges);
    }

    $stmt->bind_param('sii', $name, $activeFlag, $ingredientId);
    if (!$stmt->execute()) {
      throw new RuntimeException($stmt->errno === 1062 ? 'An ingredient with that name already exists.' : 'Unable to update ingredient.');
    }
  }
  $conn->commit();
} catch (Throwable $exception) {
  $conn->rollback();
  $currentStmt->close();
  $stmt->close();
  die('Ingredient update failed: ' . htmlspecialchars($exception->getMessage()));
}
$currentStmt->close();
$stmt->close();

$description = $changes
  ? 'Updated ingredient catalog: ' . implode('; ', $changes)
  : 'Reviewed ingredient catalog: no changes made (' . count($ids) . ' ingredients)';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);
header('Location: ' . publicUrl('products/formulas.php'));
exit;
