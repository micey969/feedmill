<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/formulas.php'));
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

$conn->begin_transaction();
try {
  $metadataStmt = $conn->prepare('INSERT INTO formula_metadata (formula_id, name, creator, setup_date, description, active_flag) VALUES (?, ?, ?, ?, ?, ?)');
  if (!$metadataStmt) {
    throw new RuntimeException('Unable to prepare formula metadata: ' . $conn->error);
  }
  $metadataStmt->bind_param('sssssi', $formulaId, $formulaName, $creator, $setupDate, $description, $activeFlag);
  if (!$metadataStmt->execute()) {
    if ($metadataStmt->errno === 1062) {
      throw new RuntimeException('Formula code "' . $formulaId . '" already exists. Use a new code or edit the existing formula.');
    }
    throw new RuntimeException('Unable to save formula metadata: ' . $metadataStmt->error);
  }
  $metadataStmt->close();

  $ingredientStmt = $conn->prepare('SELECT ingredients_id FROM ingredients WHERE ingredients_id = ? AND active_flag = 1');
  $insertStmt = $conn->prepare('INSERT INTO formula_composition (formula_id, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');
  if (!$ingredientStmt || !$insertStmt) {
    throw new RuntimeException('Unable to prepare formula ingredients: ' . $conn->error);
  }
  foreach ($ingredientIds as $index => $ingredientId) {
    $ingredientId = filter_var($ingredientId, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantities[$index], FILTER_VALIDATE_FLOAT);
    if ($ingredientId === false || $ingredientId <= 0 || $quantity === false || $quantity < 0) {
      throw new RuntimeException('Invalid ingredient data.');
    }

    $ingredientStmt->bind_param('i', $ingredientId);
    $ingredientStmt->execute();
    if ($ingredientStmt->get_result()->num_rows !== 1) {
      throw new RuntimeException('One or more ingredients are inactive or unavailable.');
    }

    $insertStmt->bind_param('sid', $formulaId, $ingredientId, $quantity);
    if (!$insertStmt->execute()) {
      throw new RuntimeException('Unable to save formula composition.');
    }
  }
  $ingredientStmt->close();
  $insertStmt->close();
  $conn->commit();
} catch (Throwable $exception) {
  $conn->rollback();
  die('Formula save failed: ' . htmlspecialchars($exception->getMessage()));
}
logAction($conn, $_SESSION['user'] ?? 'unknown', "ADD", $details = "Added new formula: $formulaId - $formulaName");

header('Location: ' . publicUrl('products/feedlist.php'));
exit;
