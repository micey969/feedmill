<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . publicUrl('products/feedlist.php'));
  exit;
}

$formulaId = trim((string) ($_POST['formula_id'] ?? ''));
$formulaName = trim((string) ($_POST['formula_name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$setupDate = trim((string) ($_POST['setup_date'] ?? ''));
$activeFlag = (int) ($_POST['active_flag'] ?? 0) === 1 ? 1 : 0;
$ingredientIds = $_POST['ingredients'] ?? [];
$quantities = $_POST['quantities'] ?? [];

if ($formulaId === '' || $formulaName === '' || $setupDate === '' || count($ingredientIds) !== count($quantities) || count($ingredientIds) === 0) {
  die('Invalid formula data.');
}

$conn->begin_transaction();
try {
  $metadataStmt = $conn->prepare('UPDATE formula_metadata SET name = ?, setup_date = ?, description = ?, active_flag = ? WHERE formula_id = ?');
  $metadataStmt->bind_param('sssis', $formulaName, $setupDate, $description, $activeFlag, $formulaId);
  $metadataStmt->execute();
  $metadataStmt->close();

  $insertStmt = $conn->prepare('INSERT INTO formula_composition (formula_id, ingredients_id, quantity_kgs) VALUES (?, ?, ?)');
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

header('Location: ' . publicUrl('products/feedlist.php'));
exit;