<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$sheetCountResult = $conn->query('SELECT COUNT(DISTINCT mixing_sheet_id) AS sheet_count FROM mixing_sheet_ingredient_usage');
$sheetCount = $sheetCountResult ? (int) $sheetCountResult->fetch_assoc()['sheet_count'] : 0;
$firstSheetId = null;
$lastSheetId = null;
$sheetId = null;
$sheetPosition = 0;
$previousSheetId = null;
$nextSheetId = null;
$sheet = null;
$ingredients = [];
$calculatedIngredientTotal = 0;
$actualIngredientTotal = 0;
$ingredientVarianceTotal = 0;

if ($sheetCount > 0) {
  $boundsResult = $conn->query('SELECT MIN(mixing_sheet_id) AS first_id, MAX(mixing_sheet_id) AS last_id FROM mixing_sheet_ingredient_usage');
  $bounds = $boundsResult->fetch_assoc();
  $firstSheetId = (int) $bounds['first_id'];
  $lastSheetId = (int) $bounds['last_id'];

  $requestedSheetId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
  $sheetId = $requestedSheetId && $requestedSheetId > 0 ? $requestedSheetId : $lastSheetId;
  $existsStmt = $conn->prepare('SELECT 1 FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id = ? LIMIT 1');
  $existsStmt->bind_param('i', $sheetId);
  $existsStmt->execute();
  if (!$existsStmt->get_result()->fetch_row()) {
    $sheetId = $lastSheetId;
  }
  $existsStmt->close();

  $positionStmt = $conn->prepare('SELECT COUNT(DISTINCT mixing_sheet_id) FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id <= ?');
  $positionStmt->bind_param('i', $sheetId);
  $positionStmt->execute();
  $positionStmt->bind_result($sheetPosition);
  $positionStmt->fetch();
  $positionStmt->close();
  $sheetPosition = (int) $sheetPosition;

  $previousStmt = $conn->prepare('SELECT MAX(mixing_sheet_id) FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id < ?');
  $previousStmt->bind_param('i', $sheetId);
  $previousStmt->execute();
  $previousStmt->bind_result($previousSheetId);
  $previousStmt->fetch();
  $previousStmt->close();
  $previousSheetId = $previousSheetId === null ? null : (int) $previousSheetId;

  $nextStmt = $conn->prepare('SELECT MIN(mixing_sheet_id) FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id > ?');
  $nextStmt->bind_param('i', $sheetId);
  $nextStmt->execute();
  $nextStmt->bind_result($nextSheetId);
  $nextStmt->fetch();
  $nextStmt->close();
  $nextSheetId = $nextSheetId === null ? null : (int) $nextSheetId;

  $usageStmt = $conn->prepare('SELECT mixing_sheet_id, formula_id, ingredient_usage_id, ingredients, date_time, required_production_tons, calculated_bags_produced, actual_bags_produced, variance_bags_produced, calculated_total_used_kgs, actual_total_used_kgs, total_variance_kgs FROM mixing_sheet_ingredient_usage WHERE mixing_sheet_id = ? ORDER BY ingredient_usage_id');
  $usageStmt->bind_param('i', $sheetId);
  $usageStmt->execute();
  $usageResult = $usageStmt->get_result();
  while ($row = $usageResult->fetch_assoc()) {
    if ($sheet === null) {
      $sheet = $row;
    }
    $ingredients[] = $row;
    $calculatedQuantity = (int) $row['calculated_total_used_kgs'];
    $actualQuantity = (int) ($row['actual_total_used_kgs'] ?? 0);
    $calculatedIngredientTotal += $calculatedQuantity;
    $actualIngredientTotal += $actualQuantity;
    $ingredientVarianceTotal += $actualQuantity - $calculatedQuantity;
  }
  $usageStmt->close();
}

$pageTitle = 'ECGC - Materials Used';

function variancePageUrl(?int $id): string {
  return htmlspecialchars(publicUrl('production/variance.php' . ($id === null ? '' : '?id=' . $id)), ENT_QUOTES, 'UTF-8');
}

function varianceNumber($value, int $decimals = 2): string {
  return number_format((float) $value, $decimals, '.', ',');
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../../app/views/includes/head.php'; ?>
<style>
  @media print {
    body * { visibility: hidden; }
    #printable-content, #printable-content * { visibility: visible; }
    #printable-content { position: absolute; left: 0; top: 0; width: 100%; }
    aside, header, .no-print { display: none !important; }
  }
</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <div class="h-1.5 bg-red-600 w-full no-print"></div>

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span><span class="text-slate-600">Production</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Materials Used</h1>
      </div>

      <div class="flex items-center gap-3 no-print">
        <nav aria-label="Mixing sheet variance pagination" class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <?php foreach ([['Last Record', $sheetId === $lastSheetId ? null : $lastSheetId, 'M11 19l-7-7 7-7m8 14l-7-7 7-7'], ['Next Record', $nextSheetId, 'M15 19l-7-7 7-7']] as [$label, $targetId, $path]): ?>
            <?php if ($targetId !== null): ?>
              <a href="<?php echo variancePageUrl($targetId); ?>" title="<?php echo $label; ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $path; ?>"></path></svg>
              </a>
            <?php else: ?>
              <span title="<?php echo $label; ?>" class="px-2 py-1 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $path; ?>"></path></svg>
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
          <span class="px-3 font-semibold text-slate-700"><?php echo $sheetCount > 0 ? $sheetPosition . ' of ' . $sheetCount : '0 of 0'; ?></span>
          <?php foreach ([['Previous Record', $previousSheetId, 'M9 5l7 7-7 7'], ['First Record', $sheetId === $firstSheetId ? null : $firstSheetId, 'M13 5l7 7-7 7M5 5l7 7-7 7']] as [$label, $targetId, $path]): ?>
            <?php if ($targetId !== null): ?>
              <a href="<?php echo variancePageUrl($targetId); ?>" title="<?php echo $label; ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $path; ?>"></path></svg>
              </a>
            <?php else: ?>
              <span title="<?php echo $label; ?>" class="px-2 py-1 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $path; ?>"></path></svg>
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
      </div>
    </header>

    <div id="printable-content" class="p-6 sm:p-8 max-w-6xl space-y-6">
      <?php if (isset($_GET['success'])): ?>
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm" role="status">Variance saved.</div>
      <?php endif; ?>

      <?php if ($sheet === null): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500">No mixing sheet ingredient usage records were found.</div>
      <?php else: ?>
        <form action="<?php echo htmlspecialchars(publicUrl('production/variance_save.php'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="space-y-6">
          <input type="hidden" name="mixing_sheet_id" value="<?php echo (int) $sheetId; ?>">

          <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sheet Number</label>
              <p class="text-base font-mono font-bold text-slate-900"><?php echo (int) $sheetId; ?></p>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date</label>
              <p class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars(date('n/j/Y', strtotime($sheet['date_time'])), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Feed Type</label>
              <p class="text-sm font-mono font-bold text-red-600"><?php echo htmlspecialchars($sheet['formula_id'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </section>

          <section class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bags Produced</p>
              <p class="text-xl font-mono font-bold text-slate-900 mt-1"><?php echo varianceNumber($sheet['calculated_bags_produced']); ?></p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
              <label for="actual-bags" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Actual Bags</label>
              <input id="actual-bags" name="actual_bags" type="number" min="0" max="9999.99" step="0.01" required value="<?php echo htmlspecialchars((string) ($sheet['actual_bags_produced'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1 text-sm font-mono font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bag Variance</p>
              <p id="bag-variance" class="text-xl font-mono font-bold text-red-600 mt-1"><?php echo varianceNumber((float) ($sheet['actual_bags_produced'] ?? 0) - (float) $sheet['calculated_bags_produced']); ?></p>
            </div>
          </section>

          <section class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Raw Ingredients Usage &amp; Variance</h2>
              <span class="text-xs text-slate-400 font-medium">Quantities measured in Kg</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 px-5">Raw Ingredient</th>
                    <th class="py-3 px-5 text-right w-44">Quantity Used</th>
                    <th class="py-3 px-5 text-right w-48">Actual Qty Used</th>
                    <th class="py-3 px-5 text-right w-44">Variance</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                  <?php foreach ($ingredients as $ingredient): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                      <td class="py-3 px-5 font-bold text-slate-900"><?php echo htmlspecialchars($ingredient['ingredients'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="py-3 px-5 text-right font-mono font-semibold"><?php echo varianceNumber($ingredient['calculated_total_used_kgs']); ?></td>
                      <td class="py-3 px-5">
                        <input type="number" min="0" max="8388607" step="1" required name="actual_qty[<?php echo (int) $ingredient['ingredient_usage_id']; ?>]" value="<?php echo htmlspecialchars((string) ($ingredient['actual_total_used_kgs'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-calculated="<?php echo (int) $ingredient['calculated_total_used_kgs']; ?>" class="actual-quantity w-full text-right bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 font-mono text-xs font-semibold focus:bg-white focus:ring-1 focus:ring-red-600">
                      </td>
                      <td class="py-3 px-5 text-right font-mono font-semibold ingredient-variance"><?php echo varianceNumber((int) ($ingredient['actual_total_used_kgs'] ?? 0) - (int) $ingredient['calculated_total_used_kgs']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot><tr class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                  <td class="py-3.5 px-5 uppercase tracking-wider">Total</td>
                  <td id="calculated-total" class="py-3.5 px-5 text-right font-mono text-sm"><?php echo varianceNumber($calculatedIngredientTotal); ?></td>
                  <td id="actual-total" class="py-3.5 px-5 text-right font-mono text-sm"><?php echo varianceNumber($actualIngredientTotal); ?></td>
                  <td id="variance-total" class="py-3.5 px-5 text-right font-mono text-sm text-slate-600"><?php echo varianceNumber($ingredientVarianceTotal); ?></td>
                </tr></tfoot>
              </table>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3 no-print">
              <a href="<?php echo variancePageUrl($sheetId); ?>" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition">Undo Changes</a>
              <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Save Variance Record</span>
              </button>
            </div>
          </section>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <script>
    const actualBagsInput = document.getElementById('actual-bags');
    const bagVarianceOutput = document.getElementById('bag-variance');
    const quantityInputs = document.querySelectorAll('.actual-quantity');

    function updateVarianceTotals() {
      if (actualBagsInput && bagVarianceOutput) {
        const actualBags = Number(actualBagsInput.value || 0);
        const bagsProduced = <?php echo $sheet === null ? '0' : json_encode((float) $sheet['calculated_bags_produced']); ?>;
        bagVarianceOutput.textContent = (actualBags - bagsProduced).toFixed(2);
      }

      let actualTotal = 0;
      let varianceTotal = 0;
      quantityInputs.forEach((input) => {
        const actual = Number(input.value || 0);
        const variance = actual - Number(input.dataset.calculated || 0);
        const varianceCell = input.closest('tr').querySelector('.ingredient-variance');
        actualTotal += actual;
        varianceTotal += variance;
        varianceCell.textContent = variance.toFixed(2);
      });
      const actualTotalOutput = document.getElementById('actual-total');
      const varianceTotalOutput = document.getElementById('variance-total');
      if (actualTotalOutput) actualTotalOutput.textContent = actualTotal.toFixed(2);
      if (varianceTotalOutput) varianceTotalOutput.textContent = varianceTotal.toFixed(2);
    }

    actualBagsInput?.addEventListener('input', updateVarianceTotals);
    quantityInputs.forEach((input) => input.addEventListener('input', updateVarianceTotals));
    updateVarianceTotals();
  </script>
</body>
</html>