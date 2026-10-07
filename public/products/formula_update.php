<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/feedlist.php'));
  exit;
}

$formulaId = is_string($_POST['formula_id'] ?? null) ? trim($_POST['formula_id']) : '';
$formulaName = is_string($_POST['formula_name'] ?? null) ? trim($_POST['formula_name']) : '';
$creator = is_string($_POST['creator'] ?? null) ? trim($_POST['creator']) : '';
$description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
$setupDate = is_string($_POST['setup_date'] ?? null) ? trim($_POST['setup_date']) : '';
$activeFlag = (int) ($_POST['active_flag'] ?? 0) === 1 ? 1 : 0;
$ingredientIds = $_POST['ingredients'] ?? [];
$quantities = $_POST['quantities'] ?? [];

if ($formulaId === '' || $formulaName === '' || $setupDate === '' || !is_array($ingredientIds) || !is_array($quantities) || count($ingredientIds) !== count($quantities) || count($ingredientIds) === 0) {
  header('Location: ' . publicUrl('products/feedlist.php?error=invalid'));
  exit;
}

try {
$currentMetadataStmt = $conn->prepare('SELECT name, creator, setup_date, description, active_flag FROM formula_metadata WHERE formula_id = ?');
$currentCompositionStmt = $conn->prepare('SELECT c.ingredients_id, c.quantity_kgs, i.name FROM formula_composition c LEFT JOIN ingredients i ON i.ingredients_id = c.ingredients_id WHERE c.formula_id = ?');
$ingredientLookupStmt = $conn->prepare('SELECT name FROM ingredients WHERE ingredients_id = ?');
if (!$currentMetadataStmt || !$currentCompositionStmt || !$ingredientLookupStmt) {
  throw new RuntimeException('Unable to prepare formula audit lookup.');
}

$currentMetadataStmt->bind_param('s', $formulaId);
$currentMetadataStmt->execute();
$currentMetadata = $currentMetadataStmt->get_result()->fetch_assoc();
$currentMetadataStmt->close();
if (!$currentMetadata) {
  $currentCompositionStmt->close();
  $ingredientLookupStmt->close();
  header('Location: ' . publicUrl('products/feedlist.php?error=not_found'));
  exit;
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
    header('Location: ' . publicUrl('products/feedlist.php?error=invalid'));
    exit;
  }

  $ingredientLookupStmt->bind_param('i', $ingredientId);
  $ingredientLookupStmt->execute();
  $ingredient = $ingredientLookupStmt->get_result()->fetch_assoc();
  if (!$ingredient) {
    $ingredientLookupStmt->close();
    header('Location: ' . publicUrl('products/feedlist.php?error=ingredient'));
    exit;
  }
  $newComposition[$ingredientId] = [
    'name' => $ingredient['name'],
    'quantity' => (float) $quantity,
  ];
}
$ingredientLookupStmt->close();

$removedIngredients = array_diff_key($oldComposition, $newComposition);
if ($removedIngredients) {
  header('Location: ' . publicUrl('products/feedlist.php?error=remove_ingredient'));
  exit;
}
} catch (Throwable $exception) {
  header('Location: ' . publicUrl('products/feedlist.php?error=save'));
  exit;
}

$transactionStarted = false;
try {
  $conn->begin_transaction();
  $transactionStarted = true;
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
  $transactionStarted = false;
} catch (Throwable $exception) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  header('Location: ' . publicUrl('products/feedlist.php?error=save'));
  exit;
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
  ? 'Updated formula ' . $formulaId . ' (' . $formulaName . "):\n" . implode("\n", $changes)
  : 'Reviewed formula ' . $formulaId . ' (' . $formulaName . '): no changes made';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $auditDetails);

header('Location: ' . publicUrl('products/feedlist.php?success=updated'));
exit;