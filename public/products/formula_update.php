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

$conn->begin_transaction();
try {
  $metadataStmt = $conn->prepare('UPDATE formula_metadata SET name = ?, creator = ?, setup_date = ?, description = ?, active_flag = ? WHERE formula_id = ?');
  if (!$metadataStmt) {
    throw new RuntimeException('Unable to prepare formula metadata update: ' . $conn->error);
  }
  $metadataStmt->bind_param('ssssis', $formulaName, $creator, $setupDate, $description, $activeFlag, $formulaId);
  $metadataStmt->execute();
  $metadataStmt->close();

  $deleteStmt = $conn->prepare('DELETE FROM formula_composition WHERE formula_id = ?');
  if (!$deleteStmt) {
    throw new RuntimeException('The database user needs DELETE permission on formula_composition to remove ingredients: ' . $conn->error);
  }
  $deleteStmt->bind_param('s', $formulaId);
  if (!$deleteStmt->execute()) {
    throw new RuntimeException('Unable to replace formula composition.');
  }
  $deleteStmt->close();

  $insertStmt = $conn->prepare('INSERT INTO formula_composition (formula_id, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');
  if (!$insertStmt) {
    throw new RuntimeException('Unable to prepare formula composition insert: ' . $conn->error);
  }
  foreach ($ingredientIds as $index => $ingredientId) {
    $ingredientId = (int) $ingredientId;
    $quantity = (float) $quantities[$index];
    if ($ingredientId <= 0 || $quantity < 0) {
      throw new RuntimeException('Invalid ingredient data.');
    }
    $insertStmt->bind_param('sid', $formulaId, $ingredientId, $quantity);
    $insertStmt->execute();
  }
  $insertStmt->close();
  $conn->commit();
} catch (Throwable $exception) {
  $conn->rollback();
  die('Formula update failed: ' . htmlspecialchars($exception->getMessage()));
}

$description = 'Updated formula: ' . $formulaId . ' - ' . $formulaName . ' (' . count($ingredientIds) . ' ingredients)';
logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', $description);

header('Location: ' . publicUrl('products/feedlist.php'));
exit;