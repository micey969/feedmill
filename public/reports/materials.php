<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$startDate = is_string($_GET['start_date'] ?? null) ? trim($_GET['start_date']) : date('Y-m-01');
$endDate = is_string($_GET['end_date'] ?? null) ? trim($_GET['end_date']) : date('Y-m-d');
$start = DateTime::createFromFormat('!Y-m-d', $startDate);
$end = DateTime::createFromFormat('!Y-m-d', $endDate);
$validStart = $start && $start->format('Y-m-d') === $startDate;
$validEnd = $end && $end->format('Y-m-d') === $endDate;
$reportError = '';
if (!$validStart || !$validEnd || $startDate > $endDate) {
  $reportError = 'Please provide a valid start and end date.';
  $startDate = date('Y-m-01');
  $endDate = date('Y-m-d');
  $start = DateTime::createFromFormat('!Y-m-d', $startDate);
  $end = DateTime::createFromFormat('!Y-m-d', $endDate);
}

$startBoundary = $startDate . ' 00:00:00';
$endExclusive = (clone $end)->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
$formatDate = static fn(string $date): string => date('d-M-Y', strtotime($date));
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$varianceClass = static fn(float $value): string => $value > 0 ? 'text-emerald-700' : ($value < 0 ? 'text-red-700' : 'text-slate-500');
$ingredients = [];
$materials = [];
$grand = ['opening' => 0.0, 'receipts' => 0.0, 'usage' => 0.0, 'computed' => 0.0, 'physical' => 0.0, 'variance' => 0.0];
$openingDate = null;
$physicalDate = null;

$loadSnapshotDate = static function (string $before) use ($conn): ?string {
  $stmt = $conn->prepare('SELECT MAX(date_time) AS stock_date FROM v_ingredients_closing_stock WHERE date_time < ?');
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare a stock snapshot query.');
  }
  $stmt->bind_param('s', $before);
  $stmt->execute();
  $stockDate = $stmt->get_result()->fetch_assoc()['stock_date'] ?? null;
  $stmt->close();
  return $stockDate === null ? null : (string) $stockDate;
};

$loadSnapshot = static function (?string $snapshotDate) use ($conn): array {
  if ($snapshotDate === null) {
    return [];
  }
  $stmt = $conn->prepare(
    'SELECT ingredients_id, MAX(quantity_kgs) AS quantity_kgs
     FROM v_ingredients_closing_stock
     WHERE date_time = ?
     GROUP BY ingredients_id'
  );
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare a stock snapshot query.');
  }
  $stmt->bind_param('s', $snapshotDate);
  $stmt->execute();
  $stock = [];
  foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $stock[(int) $row['ingredients_id']] = (float) $row['quantity_kgs'];
  }
  $stmt->close();
  return $stock;
};

$loadPeriodTotals = static function (string $query) use ($conn, $startBoundary, $endExclusive): array {
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare a materials activity query.');
  }
  $stmt->bind_param('ss', $startBoundary, $endExclusive);
  $stmt->execute();
  $totals = [];
  foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $totals[(int) $row['ingredients_id']] = (float) $row['total_kgs'];
  }
  $stmt->close();
  return $totals;
};

try {
  $ingredientResult = $conn->query("SELECT ingredients_id, name FROM ingredients WHERE active_flag = b'1' ORDER BY name ASC");
  if (!$ingredientResult) {
    throw new RuntimeException('Unable to load ingredients.');
  }
  $ingredients = $ingredientResult->fetch_all(MYSQLI_ASSOC);

  $openingDate = $loadSnapshotDate($startBoundary);
  $physicalDate = $loadSnapshotDate($endExclusive);
  $openingStock = $loadSnapshot($openingDate);
  $physicalStock = $loadSnapshot($physicalDate);
  $receipts = $loadPeriodTotals(
    'SELECT ingredients_id, SUM(COALESCE(received_quantity_kgs, 0)) AS total_kgs
     FROM v_order_ingredients
     WHERE received_date_time >= ? AND received_date_time < ?
     GROUP BY ingredients_id'
  );
  $productionUsage = $loadPeriodTotals(
    'SELECT ingredients_id, SUM(COALESCE(actual_total_used_kgs, 0)) AS total_kgs
     FROM v_ingredient_usage
     WHERE date_time >= ? AND date_time < ?
     GROUP BY ingredients_id'
  );
  $soldUsage = $loadPeriodTotals(
    'SELECT ingredients_id, SUM(COALESCE(quantity_kgs, 0)) AS total_kgs
     FROM v_ingredients_sold_separately
     WHERE date_time >= ? AND date_time < ?
     GROUP BY ingredients_id'
  );

  foreach ($ingredients as $ingredient) {
    $ingredientId = (int) $ingredient['ingredients_id'];
    $opening = $openingStock[$ingredientId] ?? 0.0;
    $received = $receipts[$ingredientId] ?? 0.0;
    $usage = ($productionUsage[$ingredientId] ?? 0.0) + ($soldUsage[$ingredientId] ?? 0.0);
    $computed = $opening + $received - $usage;
    $physical = $physicalStock[$ingredientId] ?? 0.0;
    $variance = $physical - $computed;

    $materials[] = [
      'name' => (string) $ingredient['name'],
      'opening' => $opening,
      'receipts' => $received,
      'usage' => $usage,
      'computed' => $computed,
      'physical' => $physical,
      'variance' => $variance,
    ];
    foreach ($grand as $key => $total) {
      $grand[$key] += $materials[array_key_last($materials)][$key];
    }
  }
} catch (Throwable $error) {
  $reportError = 'The materials report could not be loaded. Please try again later.';
  $materials = [];
  $grand = array_map(static fn() => 0.0, $grand);
}

$ranAt = date('F j, Y g:i A');
$openingDateLabel = $openingDate ? $formatDate($openingDate) : 'No prior stock count';
$physicalDateLabel = $physicalDate ? $formatDate($physicalDate) : 'No stock count available';

if (($_GET['export'] ?? '') === 'pdf' && $reportError === '') {
  logAction($conn, $_SESSION['user'] ?? 'unknown', 'Export', 'Exported raw materials report PDF (' . $startDate . ' to ' . $endDate . ')');
  require_once __DIR__ . '/../../app/libraries/Pdf/ReportPdf.php';
  $columns = [
    ['label' => 'Product'],
    ['label' => 'Opening (Kg)', 'align' => 'right'],
    ['label' => 'Receipts (Kg)', 'align' => 'right'],
    ['label' => 'Usage (Kg)', 'align' => 'right'],
    ['label' => 'Computed Balance', 'align' => 'right'],
    ['label' => 'Physical Stock (Kg)', 'align' => 'right'],
    ['label' => 'Variance (Kg)', 'align' => 'right'],
  ];
  $pdfRows = [];
  foreach ($materials as $material) {
    $pdfRows[] = [
      $material['name'],
      number_format($material['opening'], 2),
      number_format($material['receipts'], 2),
      number_format($material['usage'], 2),
      number_format($material['computed'], 2),
      number_format($material['physical'], 2),
      number_format($material['variance'], 2),
    ];
  }
  $pdfTotals = ['Grand Total'];
  foreach (['opening', 'receipts', 'usage', 'computed', 'physical', 'variance'] as $key) {
    $pdfTotals[] = number_format($grand[$key], 2);
  }
  \App\Libraries\Pdf\ReportPdf::download('standard-table.php', [
    'reportTitle' => 'Raw Materials Report',
    'dateRange' => ['start' => $formatDate($startDate), 'end' => $formatDate($endDate)],
    'generatedAt' => $ranAt,
    'columns' => $columns,
    'rows' => $pdfRows,
    'totals' => $pdfTotals,
    'emptyMessage' => 'No active ingredients were found.',
  ], 'raw-materials-report.pdf', 'landscape');
}
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Raw Material Report';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>

    <header data-report-page-header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-1.5 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Reports</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Raw Material Report</h1>
      </div>

      <!-- Date Range Controls -->
      <div class="flex flex-wrap items-center gap-3">
        <form action="<?php echo $escape(publicUrl('reports/materials.php')); ?>" method="GET" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 text-xs">
          <span class="text-slate-500 font-medium pl-2">Range:</span>
          <input type="date" name="start_date" value="<?php echo $escape($startDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <span class="text-slate-400 font-medium">to</span>
          <input type="date" name="end_date" value="<?php echo $escape($endDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition shadow-xs">
            Apply
          </button>
        </form>
        <a href="<?php echo $escape(publicUrl('reports/materials.php') . '?' . http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'export' => 'pdf'])); ?>" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition" title="Export PDF" aria-label="Export PDF">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M5 17v3h14v-3"></path></svg>
        </a>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl space-y-6">
      <?php if ($reportError !== ''): ?>
        <div class="border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800" role="alert"><?php echo $escape($reportError); ?></div>
      <?php endif; ?>

      <!-- REPORT SHEET CARD -->
      <div class="bg-white rounded-3xl border border-slate-300 shadow-md p-8 sm:p-12 space-y-8">
        
        <!-- Report Title Block -->
        <div class="text-center border-b border-slate-200 pb-4">
          <h2 class="text-xl font-bold text-slate-900">East Caribbean Feeds Limited</h2>
          <h3 class="text-2xl font-black text-red-600 uppercase mt-1">Raw Materials Report</h3>
          <p class="text-xs font-medium text-slate-500">From <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($startDate)); ?></span> to <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($endDate)); ?></span></p>
        </div>

        <!-- Data Table Grid -->
        <div class="rounded-2xl border border-slate-200">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6" style="top: var(--report-table-sticky-top)">Product</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Opening (Kg)</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Receipts (Kg)</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Usage (Kg)</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Computed Balance</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Physical Stock (Kg)</th>
                <th class="sticky z-20 bg-slate-100 py-3.5 px-6 text-right" style="top: var(--report-table-sticky-top)">Variance (kg)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$materials): ?>
                <tr><td colspan="7" class="py-8 px-6 text-center text-slate-500">No active ingredients were found.</td></tr>
              <?php else: ?>
                <?php foreach ($materials as $material): ?>
                  <tr class="hover:bg-slate-50/50 transition">
                    <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo $escape($material['name']); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($material['opening'], 2); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($material['receipts'], 2); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($material['usage'], 2); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($material['computed'], 2); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($material['physical'], 2); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono font-semibold <?php echo $varianceClass($material['variance']); ?>"><?php echo number_format($material['variance'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <!-- Table Footer: Grand Totals -->
            <tfoot>
              <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <td class="py-4 px-6 uppercase tracking-wider">Grand Total</td>
                <td class="py-4 px-6 text-right font-mono text-sm"><?php echo number_format($grand['opening'], 2); ?></td>
                <td class="py-4 px-6 text-right font-mono text-sm"><?php echo number_format($grand['receipts'], 2); ?></td>
                <td class="py-4 px-6 text-right font-mono text-sm"><?php echo number_format($grand['usage'], 2); ?></td>
                <td class="py-4 px-6 text-right font-mono text-sm"><?php echo number_format($grand['computed'], 2); ?></td>
                <td class="py-4 px-6 text-right font-mono text-sm"><?php echo number_format($grand['physical'], 2); ?></td>
                <td class="py-4 px-6 text-right font-mono text-sm <?php echo $varianceClass($grand['variance']); ?>"><?php echo number_format($grand['variance'], 2); ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Report Footer Meta -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-[11px] font-medium text-slate-400">
          <span><?php echo $escape($ranAt); ?></span>
        </div>

      </div>
    </div>
  </main>

  <script src="<?php echo htmlspecialchars(publicUrl('assets/js/report_table_headers.js')); ?>"></script>
</body>
</html>
