<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/formulas.php'));
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

$transactionStarted = false;
$saveError = 'save';
try {
  $conn->begin_transaction();
  $transactionStarted = true;
  $metadataStmt = $conn->prepare('INSERT INTO formula_metadata (formula_id, name, creator, setup_date, description, active_flag) VALUES (?, ?, ?, ?, ?, ?)');
  if (!$metadataStmt) {
    throw new RuntimeException('Unable to prepare formula metadata: ' . $conn->error);
  }
  $metadataStmt->bind_param('sssssi', $formulaId, $formulaName, $creator, $setupDate, $description, $activeFlag);
  if (!$metadataStmt->execute()) {
    if ($metadataStmt->errno === 1062) {
      $saveError = 'duplicate';
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
  $transactionStarted = false;
} catch (Throwable $exception) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  if ($exception instanceof mysqli_sql_exception && (int) $exception->getCode() === 1062) {
    $saveError = 'duplicate';
  }
  header('Location: ' . publicUrl('products/feedlist.php?error=' . $saveError));
  exit;
}
logAction($conn, $_SESSION['user'] ?? 'unknown', "ADD", $details = "Added new formula: $formulaId - $formulaName");

header('Location: ' . publicUrl('products/feedlist.php?success=created'));
exit;
