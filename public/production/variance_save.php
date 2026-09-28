<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed.');
}

$mixingSheetId = filter_var($_POST['mixing_sheet_id'] ?? null, FILTER_VALIDATE_INT);
$actualBagsInput = $_POST['actual_bags'] ?? '';
$submittedQuantities = $_POST['actual_qty'] ?? null;

if ($mixingSheetId === false || $mixingSheetId <= 0 || !is_string($actualBagsInput) || !preg_match('/^\d+(?:\.\d{1,2})?$/D', $actualBagsInput) || !is_array($submittedQuantities)) {
  http_response_code(400);
  exit('Invalid variance submission.');
}

$actualBags = (float) $actualBagsInput;
if ($actualBags > 9999.99) {
  http_response_code(400);
  exit('Actual bags are outside the supported range.');
}

$usageStmt = $conn->prepare('SELECT ingredient_usage_id, calculated_total_used_kgs FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id = ? ORDER BY ingredient_usage_id');
if (!$usageStmt) {
  http_response_code(500);
  exit('Unable to load the mixing sheet ingredients.');
}
$usageStmt->bind_param('i', $mixingSheetId);
$usageStmt->execute();
$usageResult = $usageStmt->get_result();
$calculatedQuantities = [];
while ($row = $usageResult->fetch_assoc()) {
  $calculatedQuantities[(int) $row['ingredient_usage_id']] = (int) $row['calculated_total_used_kgs'];
}
$usageStmt->close();

if ($calculatedQuantities === [] || count($submittedQuantities) !== count($calculatedQuantities)) {
  http_response_code(400);
  exit('The submitted ingredients do not match this mixing sheet.');
}

$actualQuantities = [];
foreach ($submittedQuantities as $ingredientId => $quantityInput) {
  $ingredientId = filter_var($ingredientId, FILTER_VALIDATE_INT);
  $quantity = is_scalar($quantityInput) ? filter_var($quantityInput, FILTER_VALIDATE_INT) : false;
  if ($ingredientId === false || !array_key_exists($ingredientId, $calculatedQuantities) || $quantity === false || $quantity < 0 || $quantity > 8388607) {
    http_response_code(400);
    exit('Invalid ingredient quantity.');
  }
  $actualQuantities[$ingredientId] = $quantity;
}

if (count($actualQuantities) !== count($calculatedQuantities)) {
  http_response_code(400);
  exit('A mixing sheet ingredient is missing.');
}

$sheetStmt = $conn->prepare('SELECT calculated_bags_produced FROM mixing_sheet WHERE mixing_sheet_id = ? LIMIT 1');
$updateSheetStmt = $conn->prepare('UPDATE mixing_sheet SET actual_bags_produced = ?, variance_bags_produced = ? WHERE mixing_sheet_id = ?');
$updateUsageStmt = $conn->prepare('UPDATE ingredient_usage SET actual_total_used_kgs = ?, total_variance_kgs = ? WHERE mixing_sheet_id = ? AND ingredients_id = ?');
if (!$sheetStmt || !$updateSheetStmt || !$updateUsageStmt) {
  http_response_code(500);
  exit('Unable to prepare the variance update.');
}

$transactionStarted = false;
try {
  $sheetStmt->bind_param('i', $mixingSheetId);
  $sheetStmt->execute();
  $sheetResult = $sheetStmt->get_result();
  $sheet = $sheetResult->fetch_assoc();
  if (!$sheet) {
    throw new RuntimeException('Mixing sheet not found.');
  }

  $bagVariance = round($actualBags - (float) $sheet['calculated_bags_produced'], 2);
  $conn->begin_transaction();
  $transactionStarted = true;

  $updateSheetStmt->bind_param('ddi', $actualBags, $bagVariance, $mixingSheetId);
  if (!$updateSheetStmt->execute()) {
    throw new RuntimeException('Unable to save bag variance.');
  }

  foreach ($actualQuantities as $ingredientId => $actualQuantity) {
    $quantityVariance = $actualQuantity - $calculatedQuantities[$ingredientId];
    $updateUsageStmt->bind_param('iiii', $actualQuantity, $quantityVariance, $mixingSheetId, $ingredientId);
    if (!$updateUsageStmt->execute()) {
      throw new RuntimeException('Unable to save ingredient variance.');
    }
  }

  $conn->commit();
  $transactionStarted = false;
  logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', 'Saved variance for mixing sheet #' . $mixingSheetId);
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  http_response_code(500);
  exit('Unable to save the variance record.');
} finally {
  $sheetStmt->close();
  $updateSheetStmt->close();
  $updateUsageStmt->close();
}

header('Location: ' . publicUrl('production/variance.php?id=' . $mixingSheetId . '&success=1'));
exit;