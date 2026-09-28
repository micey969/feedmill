<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed.');
}

$formulaId = trim((string) ($_POST['formula_id'] ?? ''));
$batchInput = (string) ($_POST['batch_tonnes'] ?? '');
$productionInput = filter_var($_POST['required_production_tons'] ?? null, FILTER_VALIDATE_INT);
$millerId = filter_var($_POST['miller_user_id'] ?? null, FILTER_VALIDATE_INT);
$sheetDate = trim((string) ($_POST['sheet_date'] ?? ''));
$note = trim((string) ($_POST['note'] ?? ''));
$batchSizes = ['0.5' => 0.5, '1' => 1.0, '2' => 2.0];
$maxProductionByBatch = ['0.5' => 5, '1' => 10, '2' => 20];
$date = DateTime::createFromFormat('!Y-m-d', $sheetDate);

if ($formulaId === '' || !isset($batchSizes[$batchInput]) || $productionInput === false || $productionInput < 1 || $productionInput > 127 || $millerId === false || $millerId <= 0 || !$date || $date->format('Y-m-d') !== $sheetDate) {
  header('Location: ' . publicUrl('production/mixing.php?error=invalid'));
  exit;
}

$batchTonnes = $batchSizes[$batchInput];
$batchCount = $productionInput / $batchTonnes;
if (abs($batchCount - round($batchCount)) > 0.000001) {
  header('Location: ' . publicUrl('production/mixing.php?error=invalid'));
  exit;
}
$batchCount = (int) round($batchCount);

if ($batchCount > 10 || $productionInput > $maxProductionByBatch[$batchInput] || ($batchInput === '2' && $productionInput % 2 !== 0)) {
  header('Location: ' . publicUrl('production/mixing.php?error=invalid'));
  exit;
}

$accountStmt = $conn->prepare('SELECT user_id FROM accounts WHERE username = ? AND active_flag = 1 LIMIT 1');
$formulaStmt = $conn->prepare('SELECT ingredients_id, quantity_kgs FROM formula_metadata_composition_view WHERE formula_id = ? AND formula_active_flag = 1 AND ingredients_id IS NOT NULL AND ingredients_active_flag = 1 ORDER BY ingredients_id');
$millerStmt = $conn->prepare('SELECT user_id FROM millers WHERE user_id = ? AND active_flag = 1 LIMIT 1');
if (!$accountStmt || !$formulaStmt || !$millerStmt) {
  http_response_code(500);
  exit('Unable to prepare the mixing sheet.');
}

$username = (string) ($_SESSION['user'] ?? '');
$accountStmt->bind_param('s', $username);
$accountStmt->execute();
$account = $accountStmt->get_result()->fetch_assoc();
$formulaStmt->bind_param('s', $formulaId);
$formulaStmt->execute();
$formulaIngredients = $formulaStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$millerStmt->bind_param('i', $millerId);
$millerStmt->execute();
$miller = $millerStmt->get_result()->fetch_assoc();
$accountStmt->close();
$formulaStmt->close();
$millerStmt->close();

if (!$account || !$miller || !$formulaIngredients) {
  header('Location: ' . publicUrl('production/mixing.php?error=invalid'));
  exit;
}

$usageRows = [];
foreach ($formulaIngredients as $ingredient) {
  $perBatch = (int) round((float) $ingredient['quantity_kgs'] * $batchTonnes, 0, PHP_ROUND_HALF_UP);
  $total = $perBatch * $batchCount;
  if ($perBatch > 8388607 || $total > 8388607) {
    header('Location: ' . publicUrl('production/mixing.php?error=invalid'));
    exit;
  }
  $usageRows[] = [(int) $ingredient['ingredients_id'], $perBatch, $total];
}

$dateTime = $sheetDate . ' ' . date('H:i:s');
$accountId = (int) $account['user_id'];
$insertSheet = $conn->prepare('INSERT INTO mixing_sheet (formula_id, miller_user_id, account_user_id, date_time, note, required_production_tons, batch_configuration_tons) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insertUsage = $conn->prepare('INSERT INTO ingredient_usage (mixing_sheet_id, ingredients_id, calculated_per_batch_used_kgs, calculated_total_used_kgs) VALUES (?, ?, ?, ?)');
if (!$insertSheet || !$insertUsage) {
  http_response_code(500);
  exit('Unable to prepare the mixing sheet save.');
}

$transactionStarted = false;
try {
  $conn->begin_transaction();
  $transactionStarted = true;
  $insertSheet->bind_param('siissid', $formulaId, $millerId, $accountId, $dateTime, $note, $productionInput, $batchTonnes);
  if (!$insertSheet->execute()) {
    throw new RuntimeException('Unable to save sheet metadata.');
  }
  $mixingSheetId = (int) $conn->insert_id;
  foreach ($usageRows as [$ingredientId, $perBatch, $total]) {
    $insertUsage->bind_param('iiii', $mixingSheetId, $ingredientId, $perBatch, $total);
    if (!$insertUsage->execute()) {
      throw new RuntimeException('Unable to save ingredient usage.');
    }
  }
  $conn->commit();
  $transactionStarted = false;
  logAction($conn, $username, 'ADD', 'Created mixing sheet #' . $mixingSheetId . ' for ' . $formulaId . ' (' . $productionInput . ' tonnes)');
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  http_response_code(500);
  exit('Unable to save the mixing sheet.');
} finally {
  $insertSheet->close();
  $insertUsage->close();
}

header('Location: ' . publicUrl('production/mixing.php?page=1&saved=1'));
exit;