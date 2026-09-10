<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/feedlist.php'));
  exit;
}

$formulaId = trim((string) ($_POST['formula_id'] ?? ''));
$formulaName = trim((string) ($_POST['formula_name'] ?? ''));
$creator = trim((string) ($_POST['creator'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$setupDate = trim((string) ($_POST['setup_date'] ?? ''));
$activeFlag = (int) ($_POST['active_flag'] ?? 0) === 1 ? 1 : 0;
$ingredientIds = $_POST['ingredients'] ?? [];
$quantities = $_POST['quantities'] ?? [];

if ($formulaId === '' || $formulaName === '' || $setupDate === '' || !is_array($ingredientIds) || !is_array($quantities) || count($ingredientIds) !== count($quantities) || count($ingredientIds) === 0) {
  die('Invalid formula data.');
}

$currentMetadataStmt = $conn->prepare('SELECT name, creator, setup_date, description, active_flag FROM formula_metadata WHERE formula_id = ?');
$currentCompositionStmt = $conn->prepare('SELECT c.ingredients_id, c.quantity_kgs, i.name FROM formula_composition c LEFT JOIN ingredients i ON i.ingredients_id = c.ingredients_id WHERE c.formula_id = ?');
$ingredientLookupStmt = $conn->prepare('SELECT name FROM ingredients WHERE ingredients_id = ?');
if (!$currentMetadataStmt || !$currentCompositionStmt || !$ingredientLookupStmt) {
  die('Unable to prepare formula audit lookup: ' . htmlspecialchars($conn->error));
}

$currentMetadataStmt->bind_param('s', $formulaId);
$currentMetadataStmt->execute();
$currentMetadata = $currentMetadataStmt->get_result()->fetch_assoc();
$currentMetadataStmt->close();
if (!$currentMetadata) {
  $currentCompositionStmt->close();
  $ingredientLookupStmt->close();
  die('Formula not found.');
}

$oldComposition = [];
$currentCompositionStmt->bind_param('s', $formulaId);
$currentCompositionStmt->execute();
foreach ($currentCompositionStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
  $oldComposition[(int) $item['ingredients_id']] = [
    'name' => $item['name'] ?: 'Ingredient #' . $item['ingredients_id'],
    'quantity' => (float) $item['quantity_kgs'],
  ];
}
$currentCompositionStmt->close();

$newComposition = [];
foreach ($ingredientIds as $index => $rawIngredientId) {
  $ingredientId = filter_var($rawIngredientId, FILTER_VALIDATE_INT);
  $quantity = filter_var($quantities[$index], FILTER_VALIDATE_FLOAT);
  if ($ingredientId === false || $ingredientId <= 0 || $quantity === false || $quantity < 0 || isset($newComposition[$ingredientId])) {
    $ingredientLookupStmt->close();
    die('Invalid ingredient data.');
  }

  $ingredientLookupStmt->bind_param('i', $ingredientId);
  $ingredientLookupStmt->execute();
  $ingredient = $ingredientLookupStmt->get_result()->fetch_assoc();
  if (!$ingredient) {
    $ingredientLookupStmt->close();
    die('Ingredient #' . $ingredientId . ' was not found.');
  }
  $newComposition[$ingredientId] = [
    'name' => $ingredient['name'],
    'quantity' => (float) $quantity,
  ];
}
$ingredientLookupStmt->close();

$removedIngredients = array_diff_key($oldComposition, $newComposition);
if ($removedIngredients) {
  $removedNames = array_map(static fn ($ingredient) => $ingredient['name'], $removedIngredients);
  die('Formula update cannot remove ingredients because the database user does not have DELETE permission. Removed: ' . htmlspecialchars(implode(', ', $removedNames)) . '. Ask the administrator to grant DELETE permission on formula_composition.');
}

$conn->begin_transaction();
try {
  $metadataStmt = $conn->prepare('UPDATE formula_metadata SET name = ?, creator = ?, setup_date = ?, description = ?, active_flag = ? WHERE formula_id = ?');
  if (!$metadataStmt) {
    throw new RuntimeException('Unable to prepare formula metadata update: ' . $conn->error);
  }
  $metadataStmt->bind_param('ssssis', $formulaName, $creator, $setupDate, $description, $activeFlag, $formulaId);
  if (!$metadataStmt->execute()) {
    throw new RuntimeException('Unable to update formula metadata: ' . $metadataStmt->error);
  }
  $metadataStmt->close();

  $updateStmt = $conn->prepare('UPDATE formula_composition SET quantity_kgs = ? WHERE formula_id = ? AND ingredients_id = ?');
  $insertStmt = $conn->prepare('INSERT INTO formula_composition (formula_id, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');
  if (!$updateStmt || !$insertStmt) {
    throw new RuntimeException('Unable to prepare formula composition update: ' . $conn->error);
  }
  foreach ($newComposition as $ingredientId => $ingredient) {
    $quantity = $ingredient['quantity'];
    if (isset($oldComposition[$ingredientId])) {
      $updateStmt->bind_param('dsi', $quantity, $formulaId, $ingredientId);
      if (!$updateStmt->execute()) {
        throw new RuntimeException('Unable to update formula composition: ' . $updateStmt->error);
      }
    } else {
      $insertStmt->bind_param('sid', $formulaId, $ingredientId, $quantity);
      if (!$insertStmt->execute()) {
        throw new RuntimeException('Unable to add formula ingredient: ' . $insertStmt->error);
      }
    }
  }
  $updateStmt->close();
  $insertStmt->close();
  $conn->commit();
} catch (Throwable $exception) {
  $conn->rollback();
  die('Formula update failed: ' . htmlspecialchars($exception->getMessage()));
}

$changes = [];
$metadataChanges = [
  'name' => [$currentMetadata['name'], $formulaName],
  'creator' => [$currentMetadata['creator'], $creator],
  'effective date' => [$currentMetadata['setup_date'], $setupDate],
  'description' => [$currentMetadata['description'], $description],
  'status' => [
    (int) $currentMetadata['active_flag'] === 1 ? 'Active' : 'Inactive',
    $activeFlag === 1 ? 'Active' : 'Inactive',
  ],
];
foreach ($metadataChanges as $field => [$oldValue, $newValue]) {
  if ((string) $oldValue !== (string) $newValue) {
    $changes[] = $field . ' "' . $oldValue . '" -> "' . $newValue . '"';
  }
}

foreach (array_diff_key($newComposition, $oldComposition) as $ingredient) {
  $changes[] = 'added ' . $ingredient['name'] . ' (' . number_format($ingredient['quantity'], 2) . ' kg)';
}
foreach (array_diff_key($oldComposition, $newComposition) as $ingredient) {
  $changes[] = 'removed ' . $ingredient['name'] . ' (' . number_format($ingredient['quantity'], 2) . ' kg)';
}
foreach (array_intersect_key($newComposition, $oldComposition) as $ingredientId => $ingredient) {
  if (abs($ingredient['quantity'] - $oldComposition[$ingredientId]['quantity']) > 0.00001) {
    $changes[] = 'quantity for ' . $ingredient['name'] . ' ' . number_format($oldComposition[$ingredientId]['quantity'], 2) . ' kg -> ' . number_format($ingredient['quantity'], 2) . ' kg';
  }
}

$auditDetails = $changes
  ? 'Updated formula ' . $formulaId . ' (' . $formulaName . '): ' . implode('; ', $changes)
  : 'Reviewed formula ' . $formulaId . ' (' . $formulaName . '): no changes made';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $auditDetails);

header('Location: ' . publicUrl('products/feedlist.php'));
exit;