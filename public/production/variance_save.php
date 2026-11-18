<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed.');
}

$mixingSheetId = filter_var($_POST['mixing_sheet_id'] ?? null, FILTER_VALIDATE_INT);
$redirect = static function (string $status) use ($mixingSheetId): never {
  $query = [];
  if (is_int($mixingSheetId) && $mixingSheetId > 0) {
    $query['id'] = $mixingSheetId;
  }
  $query['error'] = $status;
  header('Location: ' . publicUrl('production/variance.php') . '?' . http_build_query($query));
  exit;
};
$actualBagsInput = $_POST['actual_bags'] ?? '';
$submittedQuantities = $_POST['actual_qty'] ?? [];
$lastModified = is_string($_POST['last_modified'] ?? null) ? $_POST['last_modified'] : '';

if ($mixingSheetId === false || $mixingSheetId <= 0 || !is_string($actualBagsInput) || !is_array($submittedQuantities)) {
  $redirect('invalid');
}

$actualBagsInput = trim($actualBagsInput);
if ($actualBagsInput !== '' && (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $actualBagsInput) || (float) $actualBagsInput > 9999.99)) {
  $redirect('invalid');
}
$actualBags = $actualBagsInput === '' ? null : (float) $actualBagsInput;

$usageStmt = null;
$sheetStmt = $conn->prepare('SELECT calculated_bags_produced, actual_bags_produced, last_modified FROM mixing_sheet WHERE mixing_sheet_id = ? LIMIT 1 FOR UPDATE');
$updateSheetStmt = $conn->prepare('UPDATE mixing_sheet SET actual_bags_produced = ?, variance_bags_produced = ? WHERE mixing_sheet_id = ?');
$updateUsageStmt = $conn->prepare('UPDATE ingredient_usage SET actual_total_used_kgs = ?, total_variance_kgs = ? WHERE mixing_sheet_id = ? AND ingredients_id = ?');
if (!$sheetStmt || !$updateSheetStmt || !$updateUsageStmt) {
  $redirect('save');
}

$transactionStarted = false;
$failureStatus = 'save';
try {
  if (!$conn->begin_transaction()) {
    throw new RuntimeException('Unable to start the variance transaction.');
  }
  $transactionStarted = true;
  $sheetStmt->bind_param('i', $mixingSheetId);
  if (!$sheetStmt->execute()) {
    throw new RuntimeException('Unable to load the mixing sheet.');
  }
  $sheetResult = $sheetStmt->get_result();
  if (!$sheetResult) {
    throw new RuntimeException('Unable to read the mixing sheet.');
  }
  $sheet = $sheetResult->fetch_assoc();
  if (!$sheet) {
    throw new RuntimeException('Mixing sheet not found.');
  }
  if ((string) $sheet['last_modified'] !== $lastModified) {
    $failureStatus = 'conflict';
    throw new RuntimeException('Mixing sheet was modified by another user.');
  }

  $usageStmt = $conn->prepare('SELECT ingredients_id, calculated_total_used_kgs, actual_total_used_kgs FROM v_ingredient_usage WHERE mixing_sheet_id = ? ORDER BY ingredients_id');
  if (!$usageStmt) {
    throw new RuntimeException('Unable to load the mixing sheet ingredients.');
  }
  $usageStmt->bind_param('i', $mixingSheetId);
  if (!$usageStmt->execute()) {
    throw new RuntimeException('Unable to load the mixing sheet ingredients.');
  }
  $usageResult = $usageStmt->get_result();
  if (!$usageResult) {
    throw new RuntimeException('Unable to read the mixing sheet ingredients.');
  }
  $calculatedQuantities = [];
  $quantitiesComplete = true;
  foreach ($usageResult->fetch_all(MYSQLI_ASSOC) as $row) {
    $calculatedQuantities[(int) $row['ingredients_id']] = (float) $row['calculated_total_used_kgs'];
    if ($row['actual_total_used_kgs'] === null) {
      $quantitiesComplete = false;
    }
  }
  if (!$calculatedQuantities) {
    $failureStatus = 'invalid';
    throw new RuntimeException('No ingredient usage was found.');
  }
  if ($quantitiesComplete && $sheet['actual_bags_produced'] !== null) {
    $failureStatus = 'finalized';
    throw new RuntimeException('The variance is already finalized.');
  }
  if ($quantitiesComplete && $submittedQuantities !== []) {
    $failureStatus = 'conflict';
    throw new RuntimeException('Ingredient quantities were already saved.');
  }
  if ($quantitiesComplete && $actualBags === null) {
    $failureStatus = 'invalid';
    throw new RuntimeException('Actual bags are required to finalize the variance.');
  }

  $actualQuantities = [];
  if (!$quantitiesComplete) {
    if (count($submittedQuantities) !== count($calculatedQuantities)) {
      $failureStatus = 'invalid';
      throw new RuntimeException('All ingredient quantities are required.');
    }
    foreach ($submittedQuantities as $ingredientId => $quantityInput) {
      $ingredientId = filter_var($ingredientId, FILTER_VALIDATE_INT);
      $quantityInput = is_scalar($quantityInput) ? trim((string) $quantityInput) : '';
      $quantity = is_numeric($quantityInput) && is_finite((float) $quantityInput) && (float) $quantityInput >= 0 && (float) $quantityInput <= 99999.99
        ? (int) round((float) $quantityInput)
        : false;
      if ($ingredientId === false || !array_key_exists($ingredientId, $calculatedQuantities) || $quantity === false || $quantity < 0 || $quantity > 99999) {
        $failureStatus = 'invalid';
        throw new RuntimeException('Invalid ingredient quantity.');
      }
      $actualQuantities[$ingredientId] = $quantity;
    }
    if (count($actualQuantities) !== count($calculatedQuantities)) {
      $failureStatus = 'invalid';
      throw new RuntimeException('All ingredient quantities are required.');
    }
  }

  if ($actualBags !== null) {
    $bagVariance = round($actualBags - (float) $sheet['calculated_bags_produced'], 2);
    $updateSheetStmt->bind_param('ddi', $actualBags, $bagVariance, $mixingSheetId);
    if (!$updateSheetStmt->execute()) {
      throw new RuntimeException('Unable to save bag variance.');
    }
  }

  foreach ($actualQuantities as $ingredientId => $actualQuantity) {
    $quantityVariance = $actualQuantity - $calculatedQuantities[$ingredientId];
    $updateUsageStmt->bind_param('idii', $actualQuantity, $quantityVariance, $mixingSheetId, $ingredientId);
    if (!$updateUsageStmt->execute()) {
      throw new RuntimeException('Unable to save ingredient variance.');
    }
  }

  if (!$conn->commit()) {
    throw new RuntimeException('Unable to commit the variance transaction.');
  }
  $transactionStarted = false;
  logAction($conn, $_SESSION['user'] ?? 'unknown', 'UPDATE', 'Saved variance for mixing sheet #' . $mixingSheetId);
} catch (Throwable $error) {
  if ($transactionStarted) {
    $conn->rollback();
  }
  error_log('variance_save: ' . $error->getMessage());
  $redirect($failureStatus);
} finally {
  $sheetStmt->close();
  $updateSheetStmt->close();
  $updateUsageStmt->close();
  if ($usageStmt instanceof mysqli_stmt) {
    $usageStmt->close();
  }
}

header('Location: ' . publicUrl('production/variance.php?id=' . $mixingSheetId . '&success=1'));
exit;