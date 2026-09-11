<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$start = DateTime::createFromFormat('!Y-m-d', $startDate);
$end = DateTime::createFromFormat('!Y-m-d', $endDate);
$validStart = $start && $start->format('Y-m-d') === $startDate;
$validEnd = $end && $end->format('Y-m-d') === $endDate;

if (!$validStart || !$validEnd || $startDate > $endDate) {
  http_response_code(400);
  exit('Please provide a valid date range.');
}

$endExclusive = (clone $end)->modify('+1 day')->format('Y-m-d');
$salesByDate = [];
$ingredientTotals = [];
$ingredients = [];
$grandTotal = 0;

$ingredientResult = $conn->query(
  'SELECT DISTINCT ingredients
   FROM ingredients_sold_separately_view
   WHERE ingredients IS NOT NULL AND ingredients <> ""
   ORDER BY ingredients ASC'
);

if ($ingredientResult) {
  while ($ingredientRow = $ingredientResult->fetch_assoc()) {
    $ingredients[] = (string) $ingredientRow['ingredients'];
  }
}

$salesStmt = $conn->prepare(
  'SELECT DATE(date_time) AS sale_date, ingredients, SUM(quantity_kgs) AS total_quantity
   FROM ingredients_sold_separately_view
   WHERE date_time >= ? AND date_time < ?
   GROUP BY DATE(date_time), ingredients
   ORDER BY sale_date ASC, ingredients ASC'
);

if (!$salesStmt) {
  http_response_code(500);
  exit('Unable to prepare the sales report.');
}

$salesStmt->bind_param('ss', $startDate, $endExclusive);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();

while ($row = $salesResult->fetch_assoc()) {
  $row['total_quantity'] = (float) $row['total_quantity'];
  $ingredient = (string) $row['ingredients'];
  $saleDate = (string) $row['sale_date'];
  $salesByDate[$saleDate][$ingredient] = $row['total_quantity'];
  $ingredientTotals[$ingredient] = ($ingredientTotals[$ingredient] ?? 0) + $row['total_quantity'];
  $grandTotal += $row['total_quantity'];
}

$salesStmt->close();
$ingredientCount = count($ingredients);
$ranAt = date('F j, Y g:i A');
$formatDate = static fn(string $date): string => date('d-M-Y', strtotime($date));
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Items Sold Separately Report';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<style>
  @media print {
    @page {
      margin: 14mm 12mm 18mm;
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

    aside,
    header,
    .no-print {
      display: none !important;
    }

    .print-footer {
      position: static;
      border-top: 1px solid #cbd5e1;
      padding-top: 6px;
      margin-top: 24px;
      break-inside: avoid;
      page-break-inside: avoid;
    }
  }

</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <div class="h-1.5 bg-red-600 w-full"></div>

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Reports</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Items Sold Separately Report</h1>
      </div>

      <!-- Date Range Controls & Print Trigger -->
      <div class="flex flex-wrap items-center gap-3">
        <form action="<?php echo htmlspecialchars(publicUrl('reports/sold.php')); ?>" method="GET" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 text-xs">
          <span class="text-slate-500 font-medium pl-2">Range:</span>
          <input type="date" name="start_date" value="<?php echo $escape($startDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <span class="text-slate-400 font-medium">to</span>
          <input type="date" name="end_date" value="<?php echo $escape($endDate); ?>" required class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition shadow-xs">
            Apply
          </button>
        </form>

        <button type="button" onclick="logReportPrint('Items Sold Separately Report', this)" data-print-log-endpoint="<?php echo htmlspecialchars(publicUrl('reports/print_log.php')); ?>" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition" title="Print Report" aria-label="Print report">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl">
      
      <!-- REPORT SHEET CARD -->
      <div id="printable-report" class="bg-white rounded-3xl border border-slate-300 shadow-md p-8 sm:p-12 space-y-8">
        
        <!-- Report Title Block -->
        <div class="text-center space-y-2">
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Items Sold Separately Report</h2>
          <p class="text-xs font-medium text-slate-500">
            From <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($startDate)); ?></span>
            to <span class="font-semibold text-slate-800"><?php echo $escape($formatDate($endDate)); ?></span>
          </p>
        </div>

        <!-- Data Table Grid -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Date</th>
                <?php foreach ($ingredients as $ingredient): ?>
                  <th class="py-3.5 px-6 text-right"><?php echo $escape($ingredient); ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$salesByDate): ?>
                <tr>
                  <td colspan="<?php echo $ingredientCount + 1; ?>" class="py-8 px-6 text-center text-slate-500">No items sold separately in this date range.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($salesByDate as $saleDate => $sales): ?>
                  <tr class="hover:bg-slate-50/50 transition">
                    <td class="py-3.5 px-6 font-mono font-semibold"><?php echo $escape($formatDate($saleDate)); ?></td>
                    <?php foreach ($ingredients as $ingredient): ?>
                      <td class="py-3.5 px-6 text-right font-mono"><?php echo number_format($sales[$ingredient] ?? 0, 2); ?></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot>
              <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <td class="py-3.5 px-6 uppercase tracking-wider leading-tight">
                  <span class="block">Grand Total</span>
                  <span class="block mt-1 font-mono text-sm"><?php echo number_format($grandTotal, 2); ?></span>
                </td>
                <?php foreach ($ingredients as $ingredient): ?>
                  <td class="py-3.5 px-6 text-right font-mono text-sm align-bottom"><?php echo number_format($ingredientTotals[$ingredient] ?? 0, 2); ?></td>
                <?php endforeach; ?>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Report Footer Meta -->
        <div class="print-footer pt-6 border-t border-slate-100 flex items-center justify-between text-[11px] font-medium text-slate-400">
          <span><?php echo $escape($ranAt); ?></span>
          <span>Page 1</span>
        </div>

      </div>

    </div>
  </main>

</body>
</html>
