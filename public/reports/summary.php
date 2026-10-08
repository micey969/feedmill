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
  $end = DateTime::createFromFormat('!Y-m-d', $endDate);
}

$endExclusive = (clone $end)->modify('+1 day')->format('Y-m-d');
$formulas = [];
$grand = ['target' => 0.0, 'actual' => 0.0, 'variance' => 0.0, 'bags' => 0.0, 'bag_variance' => 0.0];

try {
  // Bag columns repeat on every ingredient row of a sheet, so MAX returns the per-sheet value.
  $stmt = $conn->prepare(
    'SELECT formula_id, mixing_sheet_id, DATE(MIN(date_time)) AS sheet_date,
            SUM(COALESCE(calculated_total_used_kgs, 0)) AS target_kgs,
            SUM(COALESCE(actual_total_used_kgs, 0)) AS actual_kgs,
            SUM(COALESCE(total_variance_kgs, 0)) AS variance_kgs,
            MAX(COALESCE(actual_bags_produced, 0)) AS actual_bags,
            MAX(COALESCE(variance_bags_produced, 0)) AS bag_variance
     FROM v_ingredient_usage
     WHERE date_time >= ? AND date_time < ?
     GROUP BY formula_id, mixing_sheet_id
     ORDER BY formula_id ASC, sheet_date ASC, mixing_sheet_id ASC'
  );
  if (!$stmt) {
    throw new RuntimeException('Unable to prepare the summary report.');
  }
  $stmt->bind_param('ss', $startDate, $endExclusive);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $formula = (string) $row['formula_id'];
    $sheet = [
      'date' => (string) $row['sheet_date'],
      'sheet' => (string) $row['mixing_sheet_id'],
      'target' => (float) $row['target_kgs'],
      'actual' => (float) $row['actual_kgs'],
      'variance' => (float) $row['variance_kgs'],
      'bags' => (float) $row['actual_bags'],
      'bag_variance' => (float) $row['bag_variance'],
    ];
    if (!isset($formulas[$formula])) {
      $formulas[$formula] = [
        'sheets' => [],
        'totals' => ['target' => 0.0, 'actual' => 0.0, 'variance' => 0.0, 'bags' => 0.0, 'bag_variance' => 0.0],
      ];
    }
    $formulas[$formula]['sheets'][] = $sheet;
    foreach ($grand as $key => $value) {
      $formulas[$formula]['totals'][$key] += $sheet[$key];
      $grand[$key] += $sheet[$key];
    }
  }
  $stmt->close();
} catch (Throwable $error) {
  $reportError = 'The summary report could not be loaded. Please try again later.';
  $formulas = [];
  $grand = array_map(static fn() => 0.0, $grand);
}

$ranAt = date('F j, Y g:i A');
$formatDate = static fn(string $date): string => date('d-M-Y', strtotime($date));
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$num = static fn(float $value): string => number_format($value, 2);
$signed = static fn(float $value): string => ($value > 0 ? '+' : '') . number_format($value, 2);
$varClass = static fn(float $value): string => $value > 0 ? 'text-emerald-600' : ($value < 0 ? 'text-red-600' : 'text-slate-400');
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Production Summary Report';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<style>
  @media print {
    @page {
      size: A4 portrait;
      margin: 15mm 12mm 20mm 12mm;
    }
    body * {
      visibility: hidden;
    }
    #printable-report, #printable-report * {
      visibility: visible;
    }
    #printable-report {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
    }
    .report-table-wrapper {
      overflow: visible !important;
    }
    aside, header, .no-print {
      display: none !important;
    }
    tr {
      break-inside: avoid;
      page-break-inside: avoid;
    }
    thead {
      display: table-header-group;
    }
  }
</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-1.5 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Reports</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Production Summary Report</h1>
      </div>

      <!-- Date Range Controls & Print Trigger -->
      <div class="flex flex-wrap items-center gap-3">
        <form action="<?php echo htmlspecialchars(publicUrl('reports/summary.php')); ?>" method="GET" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 text-xs">
          <span class="text-slate-500 font-medium pl-2">Range:</span>
          <input type="date" name="start_date" value="<?php echo $escape($startDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <span class="text-slate-400 font-medium">to</span>
          <input type="date" name="end_date" value="<?php echo $escape($endDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition shadow-xs">
            Apply
          </button>
        </form>

        <button type="button" onclick="window.print()" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition" title="Print report" aria-label="Print report">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl">
      <?php if ($reportError !== ''): ?>
        <div class="no-print mb-4 border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800" role="alert"><?php echo $escape($reportError); ?></div>
      <?php endif; ?>

      <!-- REPORT SHEET CARD -->
      <div id="printable-report" class="bg-white rounded-3xl border border-slate-300 shadow-md p-8 sm:p-12 space-y-8">

        <!-- Report Title Block -->
        <div class="text-center space-y-2">
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Production Summary Report</h2>
          <p class="text-xs font-medium text-slate-500">
            From <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($startDate)); ?></span>
            to <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($endDate)); ?></span>
          </p>
        </div>

        <!-- Data Table Grid -->
        <div class="report-table-wrapper overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Date</th>
                <th class="py-3.5 px-6">Sheet No</th>
                <th class="py-3.5 px-6 text-right">Target Usage (kg)</th>
                <th class="py-3.5 px-6 text-right">Actual Usage (kg)</th>
                <th class="py-3.5 px-6 text-right">Variance (kg)</th>
                <th class="py-3.5 px-6 text-right">Actual Bags</th>
                <th class="py-3.5 px-6 text-right">Bag Variance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$formulas): ?>
                <tr>
                  <td colspan="7" class="py-8 px-6 text-center text-slate-500">No production records in this date range.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($formulas as $formula => $data): ?>
                  <tr class="bg-slate-50 border-y border-slate-200">
                    <td colspan="7" class="py-3 px-6 font-black text-red-600 tracking-wide text-sm">FORMULA: <?php echo $escape((string) $formula); ?></td>
                  </tr>

                  <?php foreach ($data['sheets'] as $sheet): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                      <td class="py-3 px-6 font-mono text-slate-600"><?php echo $escape($formatDate($sheet['date'])); ?></td>
                      <td class="py-3 px-6 font-mono font-semibold text-slate-900"><?php echo $escape($sheet['sheet']); ?></td>
                      <td class="py-3 px-6 text-right font-mono"><?php echo $num($sheet['target']); ?></td>
                      <td class="py-3 px-6 text-right font-mono"><?php echo $num($sheet['actual']); ?></td>
                      <td class="py-3 px-6 text-right font-mono font-bold <?php echo $varClass($sheet['variance']); ?>"><?php echo $signed($sheet['variance']); ?></td>
                      <td class="py-3 px-6 text-right font-mono"><?php echo $num($sheet['bags']); ?></td>
                      <td class="py-3 px-6 text-right font-mono font-bold <?php echo $varClass($sheet['bag_variance']); ?>"><?php echo $signed($sheet['bag_variance']); ?></td>
                    </tr>
                  <?php endforeach; ?>

                  <tr class="bg-red-50/60 border-y-2 border-red-200 font-bold text-slate-900">
                    <td colspan="2" class="py-3 px-6 text-right uppercase text-[11px] font-black text-red-700">Subtotal (<?php echo $escape((string) $formula); ?>)</td>
                    <td class="py-3 px-6 text-right font-mono"><?php echo $num($data['totals']['target']); ?></td>
                    <td class="py-3 px-6 text-right font-mono"><?php echo $num($data['totals']['actual']); ?></td>
                    <td class="py-3 px-6 text-right font-mono <?php echo $varClass($data['totals']['variance']); ?>"><?php echo $signed($data['totals']['variance']); ?></td>
                    <td class="py-3 px-6 text-right font-mono"><?php echo $num($data['totals']['bags']); ?></td>
                    <td class="py-3 px-6 text-right font-mono <?php echo $varClass($data['totals']['bag_variance']); ?>"><?php echo $signed($data['totals']['bag_variance']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <td colspan="2" class="py-3.5 px-6 text-right uppercase tracking-wider">Grand Total</td>
                <td class="py-3.5 px-6 text-right font-mono text-sm"><?php echo $num($grand['target']); ?></td>
                <td class="py-3.5 px-6 text-right font-mono text-sm"><?php echo $num($grand['actual']); ?></td>
                <td class="py-3.5 px-6 text-right font-mono text-sm <?php echo $varClass($grand['variance']); ?>"><?php echo $signed($grand['variance']); ?></td>
                <td class="py-3.5 px-6 text-right font-mono text-sm"><?php echo $num($grand['bags']); ?></td>
                <td class="py-3.5 px-6 text-right font-mono text-sm <?php echo $varClass($grand['bag_variance']); ?>"><?php echo $signed($grand['bag_variance']); ?></td>
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

</body>
</html>
