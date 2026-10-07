<?php
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/middleware/auth.php';

$outstandingResult = $conn->query(
  'SELECT COUNT(*) AS outstanding_count FROM (
     SELECT mixing_sheet_id
     FROM v_ingredient_usage
     GROUP BY mixing_sheet_id
     HAVING COALESCE(MAX(actual_bags_produced), 0) = 0 AND COALESCE(MAX(actual_total_used_kgs), 0) = 0
   ) AS outstanding_sheets'
);
$outstandingCount = $outstandingResult ? (int) $outstandingResult->fetch_assoc()['outstanding_count'] : 0;

$lastStockResult = $conn->query('SELECT MAX(date_time) AS last_date FROM v_ingredients_closing_stock');
$lastStockDate = $lastStockResult ? $lastStockResult->fetch_assoc()['last_date'] : null;
$lastStockDateLabel = $lastStockDate ? date('l, j M Y', strtotime($lastStockDate)) : 'No records yet';

$pendingOrdersResult = $conn->query(
  "SELECT COUNT(DISTINCT o.order_id) AS pending_count FROM v_orders o
   WHERE EXISTS (SELECT 1 FROM v_order_ingredients oi WHERE oi.order_id = o.order_id AND COALESCE(oi.received_flag, b'0') = b'0')"
);
$pendingOrdersCount = $pendingOrdersResult ? (int) $pendingOrdersResult->fetch_assoc()['pending_count'] : 0;
?> 

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - East Caribbean Feeds Dashboard';
  require_once __DIR__ . '/../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row">
  
  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../app/views/includes/sidebar.php'; ?>
  

  <!-- ================= MAIN CONTENT AREA ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto">
    <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>
    
    <!-- Top Action Bar -->
    <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-1.5 z-10 shadow-xs">
      <div>
        <nav class="text-xs text-slate-400 font-medium">Main Hub / Operations</nav>
        <h2 class="text-lg font-bold text-slate-900">Dashboard Overview</h2>
      </div>
    </header>

    <!-- Main Workspace Dashboard Widgets -->
    <div class="p-8 space-y-6 max-w-6xl">
      
      <!-- Quick Status Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Outstanding Mixing Sheets</p>
            <h3 class="text-2xl font-black text-red-600 mt-1"><?php echo $outstandingCount; ?></h3>
          </div>
          <a href="<?php echo htmlspecialchars(publicUrl('production/variance.php')); ?>"class="p-3 bg-red-50 text-red-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          </a>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Last Physical Closing Date</p>
            <h3 class="text-md font-black text-slate-900 mt-1"><?php echo htmlspecialchars($lastStockDateLabel); ?></h3>
          </div>
          <a href="<?php echo htmlspecialchars(publicUrl('products/physical.php')); ?>" class="p-3 bg-slate-100 text-slate-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </a>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Orders</p>
            <h3 class="text-2xl font-black text-red-600 mt-1"><?php echo $pendingOrdersCount; ?></h3>
          </div>
          <a href="<?php echo htmlspecialchars(publicUrl('inventory/receive.php')); ?>" class="p-3 bg-red-100 text-red-700 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
          </a>
        </div>
      </div>

      <!-- Quick Action Panel -->
      <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
          <h3 class="text-base font-bold">Ready to process feed production?</h3>
          <p class="text-xs text-slate-400 mt-0.5">Start entering mixing sheets for today's batch.</p>
        </div>
        <a href="<?php echo htmlspecialchars(publicUrl('production/mixing.php')); ?>" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-red-600/30 whitespace-nowrap">
          + Start Mixing Sheet
        </a>
      </div>

    </div>
  </main>

</body>
</html>