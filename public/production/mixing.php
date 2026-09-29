<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

function mixingEscape($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mixingNumber($value): string {
  return number_format((float) $value, 2, '.', ',');
}

function mixingDateLabel(string $value): string {
  $timestamp = strtotime($value);
  if ($timestamp === false) {
    return '';
  }
  $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
  return date('l j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ', ' . date('Y', $timestamp);
}

function mixingBatchBoxes(int $count): void {
  for ($batch = 0; $batch < $count; $batch++) {
    echo '<span class="h-5 w-5 border border-slate-400 rounded-sm inline-block"></span>';
  }
}

$formulas = [];
$formulaIngredients = [];
$formulaResult = $conn->query('SELECT formula_id, formula_name, description, ingredients_id, ingredients, quantity_kgs FROM formula_metadata_composition_view WHERE formula_active_flag = 1 AND ingredients_id IS NOT NULL AND ingredients_active_flag = 1 ORDER BY formula_id, ingredients_id');
if ($formulaResult) {
  while ($row = $formulaResult->fetch_assoc()) {
    $formulaId = $row['formula_id'];
    if (!isset($formulas[$formulaId])) {
      $formulas[$formulaId] = ['name' => $row['formula_name'], 'description' => $row['description']];
      $formulaIngredients[$formulaId] = [];
    }
    $formulaIngredients[$formulaId][] = [
      'id' => (int) $row['ingredients_id'],
      'name' => $row['ingredients'],
      'quantity' => (float) $row['quantity_kgs'],
    ];
  }
}

$millers = [];
$millerResult = $conn->query('SELECT user_id, full_name, job_title FROM millers WHERE active_flag = 1 ORDER BY full_name');
if ($millerResult) {
  $millers = $millerResult->fetch_all(MYSQLI_ASSOC);
}

$sheet = null;
$sheetIngredients = [];
$sheetIds = [];
$sheetIdsResult = $conn->query('SELECT mixing_sheet_id FROM mixing_sheet ORDER BY mixing_sheet_id DESC');
if ($sheetIdsResult) {
  while ($row = $sheetIdsResult->fetch_assoc()) {
    $sheetIds[] = (int) $row['mixing_sheet_id'];
  }
}
$sheetCount = count($sheetIds);
$totalPages = $sheetCount + 1;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$requestedSheetId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!isset($_GET['page']) && $requestedSheetId !== false && $requestedSheetId !== null && $requestedSheetId > 0) {
  $requestedPosition = array_search($requestedSheetId, $sheetIds, true);
  if ($requestedPosition === false) {
    http_response_code(404);
  } else {
    $currentPage = $requestedPosition + 2;
  }
}
$currentPage = min($currentPage, $totalPages);
$isNewEntry = $currentPage === 1;
$sheetId = $isNewEntry ? null : ($sheetIds[$currentPage - 2] ?? null);
$nextSheetNumber = ($sheetIds[0] ?? 0) + 1;

if (!$isNewEntry && $sheetId !== null) {
  $sheetStmt = $conn->prepare('SELECT mixing_sheet.mixing_sheet_id, mixing_sheet.formula_id, mixing_sheet.miller_user_id, mixing_sheet.account_user_id, mixing_sheet.date_time, mixing_sheet.note, mixing_sheet.required_production_tons, mixing_sheet.batch_configuration_tons, millers.full_name AS miller_name, millers.job_title, formula_metadata.name AS formula_name, formula_metadata.description AS formula_description FROM mixing_sheet LEFT JOIN millers ON millers.user_id = mixing_sheet.miller_user_id LEFT JOIN formula_metadata ON formula_metadata.formula_id = mixing_sheet.formula_id WHERE mixing_sheet.mixing_sheet_id = ? LIMIT 1');
  if ($sheetStmt) {
    $sheetStmt->bind_param('i', $sheetId);
    $sheetStmt->execute();
    $sheet = $sheetStmt->get_result()->fetch_assoc();
    $sheetStmt->close();
  }
  if ($sheet) {
    $usageStmt = $conn->prepare('SELECT ingredient_usage_id, ingredients, calculated_per_batch_used_kgs, calculated_total_used_kgs FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id = ? ORDER BY ingredient_usage_id');
    if ($usageStmt) {
      $usageStmt->bind_param('i', $sheetId);
      $usageStmt->execute();
      $sheetIngredients = $usageStmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $usageStmt->close();
    }
  } else {
    http_response_code(404);
  }
}

$batchCount = $sheet && (float) $sheet['batch_configuration_tons'] > 0
  ? (int) round((int) $sheet['required_production_tons'] / (float) $sheet['batch_configuration_tons'])
  : 0;
$scaleTotal = 0;
$sheetTotal = 0;
foreach ($sheetIngredients as $ingredient) {
  $scaleTotal += (int) $ingredient['calculated_per_batch_used_kgs'];
  $sheetTotal += (int) $ingredient['calculated_total_used_kgs'];
}
?>

<!DOCTYPE html>
<html lang="en">

<?php 
  $pageTitle = 'ECGC - Mixing Sheet';
  require_once __DIR__ . '/../../app/views/includes/head.php';
?>

<style>
  .date-print-label { display: none; }
  @media print {
    body * { visibility: hidden; }
    #printable-sheet, #printable-sheet * { visibility: visible; }
    #printable-sheet { position: absolute; inset: 0; width: 100%; padding: 0; margin: 0; box-shadow: none !important; border: none !important; }
    .no-print { display: none !important; }
    .date-screen-control { display: none !important; }
    .date-print-label { display: block !important; }
  }
</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">
  
<!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  
  <main id="main-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
    
    <div class="h-1.5 bg-red-600 w-full no-print"></div>

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 no-print">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo mixingEscape(publicUrl('index.php')); ?>" class="hover:text-red-600">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Production</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Mixing Sheet</h1>
      </div>

      <!-- Action Controls -->
      <div class="flex items-center gap-3">
        <!-- Record Navigation Toolbar -->
        <nav aria-label="Mixing sheet pagination" class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <a href="<?php echo mixingEscape(publicUrl('production/mixing.php?page=1')); ?>" class="px-2 py-1 <?php echo $currentPage === 1 ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="New Entry" aria-label="New Entry">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          </a>
          <a href="<?php echo mixingEscape(publicUrl('production/mixing.php?page=' . max(1, $currentPage - 1))); ?>" class="px-2 py-1 <?php echo $currentPage === 1 ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Previous Sheet" aria-label="Previous sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </a>
          <span class="px-3 font-semibold text-slate-700" aria-live="polite"><?php echo $isNewEntry ? 'New Entry' : 'Sheet #' . (int) $sheet['mixing_sheet_id']; ?></span>
          <a href="<?php echo mixingEscape(publicUrl('production/mixing.php?page=' . min($totalPages, $currentPage + 1))); ?>" class="px-2 py-1 <?php echo $currentPage === $totalPages ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Next Sheet" aria-label="Next sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </a>
          <a href="<?php echo mixingEscape(publicUrl('production/mixing.php?page=' . $totalPages)); ?>" class="px-2 py-1 <?php echo $currentPage === $totalPages ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Last Sheet" aria-label="Last sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          </a>
        </nav>
        <button onclick="window.print()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
          <span>Print Mixing Sheet</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body / Sheet Form Container -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">

      <?php if (isset($_GET['saved'])): ?><div class="no-print border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">Mixing sheet saved.</div>
      <?php elseif (isset($_GET['error'])): ?><div class="no-print border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">The sheet could not be saved. Check the selected formula, batch size, production tonnage, and miller, then try again.</div><?php endif; ?>

      <section id="printable-sheet" class="bg-white p-8 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        <div class="text-center border-b border-slate-200 pb-4">
          <h2 class="text-xl font-bold text-slate-900">East Caribbean Feeds Limited</h2>
          <h3 class="text-2xl font-black text-red-600 uppercase mt-1">Mixing Sheet</h3>
        </div>

        <?php if ($sheet): ?>
          <?php $selectedFormula = ['name' => $sheet['formula_name'] ?? '', 'description' => $sheet['formula_description'] ?? '']; $sheetDate = $sheet['date_time'] ? mixingDateLabel($sheet['date_time']) : ''; ?>
          <!-- FORM CONTROLS & META DATA GRID -->
          <div class="grid grid-cols-2 gap-4 bg-slate-50 p-5 rounded-xl border border-slate-200">
            <div class="space-y-4">
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Select Tonnes Per Batch</span>
                <span class="text-xs font-semibold"><?php echo mixingNumber($sheet['batch_configuration_tons']); ?> TON</span>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date</span>
                <span class="text-xs font-semibold"><?php echo mixingEscape($sheetDate); ?></span>
              </div>
            </div>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sheet No.</span>
                  <span class="block bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-slate-900"><?php echo (int) $sheet['mixing_sheet_id']; ?></span>
                </div>
                <div>
                  <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Feed Code</span>
                  <span class="block bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-red-600"><?php echo mixingEscape($sheet['formula_id']); ?></span>
                </div>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Product Description</span>
                <span class="block bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800">
                  <?php echo mixingEscape($selectedFormula['name']); ?><?php if ($selectedFormula['description'] !== ''): ?>, <?php echo mixingEscape($selectedFormula['description']); ?><?php endif; ?></span>
                  </div>
            </div>
          </div>
        <?php else: ?>
          <form id="mixing-form" action="mixing_save.php" method="POST" class="space-y-6">
            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-5 rounded-xl border border-slate-200">

              <!-- Left Column Inputs -->
              <div class="space-y-4">

                <!-- Tonnes Radio Selection -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Select Tonnes Per Batch</label>
                  <div class="flex items-center gap-4 text-xs font-semibold">
                    <?php foreach (['0.5' => '1/2 TON', '1' => '1 TON', '2' => '2 TON'] as $value => $label): ?>
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="batch_tonnes" value="<?php echo $value; ?>" class="text-red-600 focus:ring-red-600" <?php echo $value === '2' ? 'checked' : ''; ?>>
                        <span><?php echo $label; ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Date Selection -->
                <div>
                  <label for="sheet-date" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date</label>
                  <input id="sheet-date" type="date" name="sheet_date" value="<?php echo date('Y-m-d'); ?>" required class="date-screen-control w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-600">
                  <span id="sheet-date-print" class="date-print-label text-xs font-semibold text-slate-800"><?php echo mixingEscape(mixingDateLabel(date('Y-m-d'))); ?></span>
                </div>
              </div>

              <!-- Right Column Inputs -->
              <div class="space-y-4">

                <!-- Sheet No & Formula Code -->
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sheet No.</label>
                    <div class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-slate-900"><?php echo $nextSheetNumber; ?></div>
                  </div>
                  <div>
                    <label for="formula-id" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Feed Code</label>
                    <select id="formula-id" name="formula_id" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-red-600 focus:outline-none focus:ring-2 focus:ring-red-600">
                      <option value="">Select a formula</option>
                      <?php foreach ($formulas as $formulaId => $formula): ?>
                        <option value="<?php echo mixingEscape($formulaId); ?>"><?php echo mixingEscape($formulaId); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <!-- Product Description -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Product Description</label>
                  <div id="formula-description" class="min-h-9 bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800"></div>
                </div>
              </div>
            </div>

            <!-- MIXING TABLE WITH BATCH CHECKLIST GRID -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse min-w-[720px]">
                <thead>
                  <tr class="border-b-2 border-slate-300 text-slate-600 font-bold uppercase tracking-wider">
                    <th class="py-2 px-3 text-right w-24">Per Batch</th>
                    <th class="py-2 px-3">Ingredients</th>
                    <th class="py-2 px-3 text-right w-28">Scale Readings</th>
                    <th class="py-2 px-3 text-center w-36">Batches<br><span id="batch-heading" class="text-[10px] font-normal text-slate-500"></span></th>
                    <th class="py-2 px-3 text-right w-28">Total</th>
                  </tr>
                </thead>
                <tbody id="ingredient-rows" class="divide-y divide-slate-200 font-medium text-slate-800">
                  <tr>
                    <td colspan="5" class="py-8 px-3 text-center text-slate-500">Select a feed code to load its ingredients.</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t-2 border-slate-300 font-bold text-slate-900">
                    <td id="per-batch-total" class="py-2.5 px-3 text-right font-mono">0.00</td>
                    <td class="py-2.5 px-3 uppercase">Totals</td>
                    <td id="scale-total" class="py-2.5 px-3 text-right font-mono">0.00</td>
                    <td></td>
                    <td id="ingredient-total" class="py-2.5 px-3 text-right font-mono text-red-600">0.00</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- FOOTER DETAILS & SIGNATURE SECTION -->
            <div class="pt-4 border-t border-slate-200 space-y-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
                <div class="flex flex-col gap-1">
                  <p id="required-production-error" class="hidden text-[10px] font-bold text-red-600"></p>
                  <div class="flex items-center gap-2">
                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">Required Production:</span>
                    <input id="required-production" type="number" name="required_production_tons" min="2" max="20" step="2" required class="w-16 bg-slate-50 border border-slate-300 rounded px-2 py-1 text-center font-mono font-bold">
                    <span>TONNES</span>
                  </div>
                </div>
                <div class="flex items-center gap-2 sm:justify-end">
                  <label for="to-bin" class="text-slate-500 uppercase tracking-wider text-[10px]">To Bin No:</label>
                  <input id="to-bin" type="text" maxlength="50" class="w-24 bg-slate-50 border border-slate-300 rounded px-2 py-1 font-mono font-bold">
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="micro-reference" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Micro Reference Number</label>
                  <textarea id="micro-reference" rows="2" maxlength="100" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium focus:bg-white focus:ring-1 focus:ring-red-600"></textarea>
                </div>
                <div>
                  <label for="sheet-note" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Notes</label>
                  <textarea id="sheet-note" name="note" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium focus:bg-white focus:ring-1 focus:ring-red-600"></textarea>
                </div>
              </div>

              <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
                <div>
                  <label for="miller-id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Miller Name</label>
                  <select id="miller-id" name="miller_user_id" required class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-medium focus:bg-white focus:ring-1 focus:ring-red-600">
                    <option value="">Select a miller</option>
                    <?php foreach ($millers as $miller): ?>
                      <option value="<?php echo (int) $miller['user_id']; ?>" data-job-title="<?php echo mixingEscape($miller['job_title']); ?>"><?php echo mixingEscape($miller['full_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="miller-position" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Position</label>
                  <input id="miller-position" type="text" readonly class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 text-xs font-medium">
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Scale Man Signature</label>
                  <div class="h-9 border border-dashed border-slate-300 rounded bg-slate-50 flex items-center justify-center text-slate-400 text-[10px] font-mono">[ Sign Here ]</div>
                </div>
              </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center justify-between no-print">
              <div class="flex items-center gap-3">
                <button type="reset" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition">Undo Changes</button>
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  <span>Save Mixing Sheet</span>
                </button>
              </div>
            </div>
          </form>
        <?php endif; ?>

        <?php if ($sheet): ?>
          <!-- MIXING TABLE WITH BATCH CHECKLIST GRID -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse min-w-[720px]">
              <thead>
                <tr class="border-b-2 border-slate-300 text-slate-600 font-bold uppercase tracking-wider">
                  <th class="py-2 px-3 text-right w-24">Per Batch (kg)</th>
                  <th class="py-2 px-3">Ingredients</th>
                  <th class="py-2 px-3 text-right w-28">Scale Reading (kg)</th>
                  <th class="py-2 px-3 text-center w-36">Batches
                    <br><span class="text-[10px] font-normal text-slate-500"> (<?php echo $batchCount; ?>)</span>
                  </th>
                  <th class="py-2 px-3 text-right w-28">Total (kg)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 font-medium text-slate-800">
                <?php $runningScale = 0; 
                foreach ($sheetIngredients as $ingredient): $perBatch = (int) $ingredient['calculated_per_batch_used_kgs']; $runningScale += $perBatch; ?>
                <tr>
                  <td class="py-2 px-3 text-right font-mono font-bold"><?php echo mixingNumber($perBatch); ?></td>
                  <td class="py-2 px-3 font-bold text-slate-900"><?php echo mixingEscape($ingredient['ingredients']); ?></td>
                  <td class="py-2 px-3 text-right font-mono text-slate-600"><?php echo mixingNumber($runningScale); ?></td>
                  <td class="py-2 px-3 text-center">
                    <div class="inline-flex gap-1 justify-center"><?php mixingBatchBoxes($batchCount); ?></div>
                  </td>
                  <td class="py-2 px-3 text-right font-mono font-bold text-slate-900"><?php echo mixingNumber($ingredient['calculated_total_used_kgs']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$sheetIngredients): ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-500">No ingredient usage rows were found for this sheet.</td>
                </tr>
                <?php endif; ?>
              </tbody>
              <tfoot>
                <tr class="border-t-2 border-slate-300 font-bold text-slate-900">
                  <td class="py-2.5 px-3 text-right font-mono"><?php echo mixingNumber($scaleTotal); ?></td>
                  <td class="py-2.5 px-3 uppercase">Totals</td>
                  <td class="py-2.5 px-3 text-right font-mono"><?php echo mixingNumber($scaleTotal); ?></td>
                  <td></td>
                  <td class="py-2.5 px-3 text-right font-mono text-red-600"><?php echo mixingNumber($sheetTotal); ?></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- FOOTER DETAILS & SIGNATURE SECTION -->
          <div class="pt-4 border-t border-slate-200 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
              <div class="flex items-center gap-2"><span class="text-slate-500 uppercase tracking-wider text-[10px]">Required Production:</span><span class="w-16 bg-slate-50 border border-slate-300 rounded px-2 py-1 text-center font-mono font-bold"><?php echo (int) $sheet['required_production_tons']; ?></span><span>TONNES</span></div>
              <div class="flex items-center gap-2 sm:justify-end"><span class="text-slate-500 uppercase tracking-wider text-[10px]">To Bin No:</span><div class="w-24 bg-slate-50 border border-slate-300 rounded px-2 py-1 font-mono font-bold"></div></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div><label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Micro Reference Number</label><div class="min-h-12 w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium whitespace-pre-wrap"></div></div>
              <div><label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Notes</label><div class="min-h-12 w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium whitespace-pre-wrap"><?php echo mixingEscape($sheet['note'] ?? ''); ?></div></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
              <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Miller Name</label><div class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 font-medium"><?php echo mixingEscape($sheet['miller_name'] ?? ''); ?></div></div>
              <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Position</label><div class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 font-medium"><?php echo mixingEscape($sheet['job_title'] ?? ''); ?></div></div>
              <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Scale Man Signature</label><div class="h-9 border border-dashed border-slate-300 rounded bg-slate-50 flex items-center justify-center text-slate-400 text-[10px] font-mono">[ Sign Here ]</div></div>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>

  <?php if (!$sheet): ?><script>
    const formulaIngredients = <?php echo json_encode($formulaIngredients, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const formulaDetails = <?php echo json_encode($formulas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const formulaSelect = document.getElementById('formula-id');
    const ingredientRows = document.getElementById('ingredient-rows');
    const batchHeading = document.getElementById('batch-heading');
    const requiredProduction = document.getElementById('required-production');
    const requiredProductionError = document.getElementById('required-production-error');
    const sheetDate = document.getElementById('sheet-date');
    const sheetDatePrint = document.getElementById('sheet-date-print');
    const batchOptions = [...document.querySelectorAll('input[name="batch_tonnes"]')];
    const millerSelect = document.getElementById('miller-id');
    const millerPosition = document.getElementById('miller-position');

    function updateMillerPosition() {
      millerPosition.value = millerSelect.selectedOptions[0]?.dataset.jobTitle || '';
    }

    function updateSheetDateDisplay() {
      if (!sheetDate.value) {
        sheetDatePrint.textContent = '';
        return;
      }
      const selectedDate = new Date(`${sheetDate.value}T00:00:00`);
      const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
      sheetDatePrint.textContent = `${weekdays[selectedDate.getDay()]} ${selectedDate.getDate()} ${months[selectedDate.getMonth()]}, ${selectedDate.getFullYear()}`;
    }

    sheetDate.addEventListener('change', updateSheetDateDisplay);

    const maxProductionByBatch = { 0.5: 5, 1: 10, 2: 20 };
    const batchTonnageLabels = { 0.5: 'HALF TON', 1: 'ONE TON', 2: 'TWO TON' };
    const MAX_BATCH_COUNT = 10;

    function updateMixingTable() {
      const batchTonnes = Number(document.querySelector('input[name="batch_tonnes"]:checked')?.value || 0);
      const maxProduction = maxProductionByBatch[batchTonnes] || 0;
      requiredProduction.max = maxProduction || '';
      requiredProduction.min = batchTonnes === 2 ? 2 : 1;
      requiredProduction.step = batchTonnes === 2 ? 2 : 1;
      let productionTonnes = Number(requiredProduction.value || 0);
      if (maxProduction && productionTonnes > maxProduction) {
        productionTonnes = maxProduction;
        requiredProduction.value = productionTonnes;
      }
      if (batchTonnes === 2 && productionTonnes % 2 !== 0) {
        productionTonnes -= 1;
        requiredProduction.value = productionTonnes;
      }
      const batchCount = batchTonnes > 0 ? Math.floor(productionTonnes / batchTonnes) : 0;
      const exactBatchCount = batchTonnes > 0 ? productionTonnes / batchTonnes : 0;
      let validityMessage = '';
      if (productionTonnes > 0) {
        if (Math.abs(exactBatchCount - Math.round(exactBatchCount)) > 0.000001) {
          validityMessage = 'Required production must be a whole number of batches.';
        } else if (batchTonnes === 2 && productionTonnes % 2 !== 0) {
          validityMessage = 'Required production must be an even number for 2 TON batches.';
        } else if (maxProduction && productionTonnes > maxProduction) {
          validityMessage = `Required production cannot exceed ${maxProduction} tonnes for ${batchTonnes} TON batches.`;
        } else if (batchCount > MAX_BATCH_COUNT) {
          validityMessage = `Number of batches cannot exceed ${MAX_BATCH_COUNT}.`;
        }
      }
      requiredProduction.setCustomValidity(validityMessage);
      requiredProductionError.textContent = validityMessage;
      requiredProductionError.classList.toggle('hidden', !validityMessage);
      batchHeading.textContent = batchTonnageLabels[batchTonnes] ? `(${batchTonnageLabels[batchTonnes]})` : '';
      const ingredients = formulaIngredients[formulaSelect.value] || [];
      const formula = formulaDetails[formulaSelect.value];
      document.getElementById('formula-description').textContent = formula ? [formula.name, formula.description].filter(Boolean).join(' - ') : '';
      if (!ingredients.length) {
        ingredientRows.innerHTML = '<tr><td colspan="5" class="py-8 px-3 text-center text-slate-500">Select a feed code to load its ingredients.</td></tr>';
        document.getElementById('per-batch-total').textContent = '0.00';
        document.getElementById('scale-total').textContent = '0.00';
        document.getElementById('ingredient-total').textContent = '0.00';
        return;
      }

      let runningScale = 0;
      let perBatchTotal = 0;
      ingredientRows.replaceChildren();
      for (const ingredient of ingredients) {
        const perBatch = Math.round(ingredient.quantity * batchTonnes);
        runningScale += perBatch;
        perBatchTotal += perBatch;
        const row = document.createElement('tr');
        const batchBoxes = document.createElement('div');
        batchBoxes.className = 'inline-flex gap-1 justify-center';
        for (let batch = 0; batch < batchCount; batch += 1) {
          const box = document.createElement('span');
          box.className = 'w-5 h-5 border border-slate-400 rounded-sm inline-block';
          batchBoxes.appendChild(box);
        }
        [perBatch.toFixed(2), ingredient.name, runningScale.toFixed(2), '', (perBatch * batchCount).toFixed(2)].forEach((value, index) => {
          const cell = document.createElement('td');
          cell.className = `py-2 px-3 ${index === 0 || index === 2 || index === 4 ? 'text-right font-mono' : ''} ${index === 0 || index === 1 || index === 4 ? 'font-bold' : 'text-slate-600'}`;
          if (index === 3) {
            cell.className = 'py-2 px-3 text-center';
            cell.appendChild(batchBoxes);
          } else {
            cell.textContent = value;
          }
          row.appendChild(cell);
        });
        ingredientRows.appendChild(row);
      }
      document.getElementById('per-batch-total').textContent = perBatchTotal.toFixed(2);
      document.getElementById('scale-total').textContent = runningScale.toFixed(2);
      document.getElementById('ingredient-total').textContent = (perBatchTotal * batchCount).toFixed(2);
    }

    formulaSelect.addEventListener('change', updateMixingTable);
    requiredProduction.addEventListener('input', updateMixingTable);
    batchOptions.forEach((option) => option.addEventListener('change', updateMixingTable));
    millerSelect.addEventListener('change', updateMillerPosition);
    updateMixingTable();
    document.getElementById('mixing-form').addEventListener('reset', () => setTimeout(() => {
      updateMixingTable();
      updateMillerPosition();
    }));
  </script><?php endif; ?>
</body>
</html>