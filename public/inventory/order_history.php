<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$recordsPerPage = 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$searchTerm = trim($_GET['search'] ?? '');
$searchParam = '%' . $searchTerm . '%';
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$countStmt = $conn->prepare(
  "SELECT COUNT(*) AS total FROM v_orders o
   WHERE CAST(o.order_id AS CHAR) LIKE ? OR o.company_name LIKE ?
      OR COALESCE(o.invoice_number, '') LIKE ?
      OR EXISTS (SELECT 1 FROM v_order_ingredients oi WHERE oi.order_id = o.order_id AND oi.name LIKE ?)"
);
$countStmt->bind_param('ssss', $searchParam, $searchParam, $searchParam, $searchParam);
$countStmt->execute();
$totalRecords = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRecords / $recordsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recordsPerPage;
$ordersStmt = $conn->prepare(
  "SELECT o.order_id, o.ordered_date_time, o.expected_arrival, o.company_name, o.invoice_number, o.transport_name, o.full_name,
          COALESCE((SELECT SUM(oi.ordered_quantity_kgs) FROM v_order_ingredients oi WHERE oi.order_id = o.order_id), 0) AS total_quantity,
          CASE WHEN EXISTS (
            SELECT 1 FROM v_order_ingredients oi
            WHERE oi.order_id = o.order_id AND COALESCE(oi.received_flag, b'0') <> b'1'
          ) THEN 'Pending' ELSE 'Completed' END AS receipt_status
   FROM v_orders o
   WHERE CAST(o.order_id AS CHAR) LIKE ? OR o.company_name LIKE ?
      OR COALESCE(o.invoice_number, '') LIKE ?
      OR EXISTS (SELECT 1 FROM v_order_ingredients oi WHERE oi.order_id = o.order_id AND oi.name LIKE ?)
   ORDER BY o.ordered_date_time DESC, o.order_id DESC LIMIT ? OFFSET ?"
);
$ordersStmt->bind_param('ssssii', $searchParam, $searchParam, $searchParam, $searchParam, $recordsPerPage, $offset);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ordersStmt->close();

$orderDetails = [];
if ($orders) {
  $orderIds = implode(',', array_map(static fn ($order): int => (int) $order['order_id'], $orders));
  $detailsResult = $conn->query(
    "SELECT order_id, ingredients_id, name, ordered_quantity_kgs, received_quantity_kgs, received_flag
     FROM v_order_ingredients WHERE order_id IN ($orderIds) ORDER BY order_id, name"
  );
  foreach ($detailsResult->fetch_all(MYSQLI_ASSOC) as $detail) {
    $orderDetails[(int) $detail['order_id']][] = $detail;
  }
}

$suppliers = $conn->query('SELECT supplier_id, company_name FROM suppliers ORDER BY company_name')->fetch_all(MYSQLI_ASSOC);
$millers = $conn->query("SELECT user_id, full_name FROM millers WHERE active_flag = b'1' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$ingredients = $conn->query("SELECT ingredients_id, name FROM ingredients WHERE active_flag = b'1' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$displayStart = $totalRecords > 0 ? $offset + 1 : 0;
$displayEnd = min($offset + $recordsPerPage, $totalRecords);
$pageUrl = static function (int $page) use ($searchTerm): string {
  $query = ['page' => $page];
  if ($searchTerm !== '') {
    $query['search'] = $searchTerm;
  }
  return publicUrl('inventory/order_history.php') . '?' . http_build_query($query);
};
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Order History';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    
    <!-- Red Header Accent Line -->
    <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>

    <!-- Page Header Bar -->
    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-1.5 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="#" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Inventory</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Order History</h1>
      </div>

      <!-- Quick Search & Create Order Trigger -->
      <div class="flex items-center gap-3">
        <form method="GET" class="relative w-64">
          <input type="search" name="search" value="<?php echo $escape($searchTerm); ?>" placeholder="Search order, ingredient, supplier..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-9 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          <div class="absolute left-3 top-2.5">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          </div>
          <?php if ($searchTerm !== ''): ?>
            <a href="<?php echo htmlspecialchars(publicUrl('inventory/order_history.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2.5 pr-3 text-slate-400 hover:text-slate-700 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
            </a>
          <?php endif; ?>
        </form>

        <!-- Add Orders -->
        <a href="<?php echo htmlspecialchars(publicUrl('inventory/orders.php')); ?>" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          <span>New Order</span>
        </a>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl space-y-4">
      
      <!-- Helper Banner -->
      <?php if (isset($_GET['edited'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-semibold">Order updated successfully.</div>
      <?php elseif (isset($_GET['edit_error'])): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-semibold">The order could not be updated. Check the entered values and try again.</div>
      <?php endif; ?>
      <div class="flex items-center justify-between bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl text-xs font-medium">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span>Manage raw bulk material purchase orders, vessel shipments, and tonnage allocations.</span>
        </div>
        <span class="font-bold text-blue-600 font-mono"><?php echo $totalRecords; ?> Orders</span>
      </div>

      <!-- Orders Data Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Order ID</th>
                <th class="py-3.5 px-6">Order Date</th>
                <th class="py-3.5 px-6">Supplier</th>
                <th class="py-3.5 px-6">Invoice Number</th>
                <th class="py-3.5 px-6 text-right">Quantity Ordered</th>
                <th class="py-3.5 px-6 text-center">Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              
              <?php if (!$orders): ?>
                <tr><td colspan="7" class="py-8 px-6 text-center text-slate-500">No orders found<?php echo $searchTerm !== '' ? ' for this search.' : '.'; ?></td></tr>
              <?php else: ?>
                <?php foreach ($orders as $order): ?>
                  <tr class="hover:bg-slate-50/80 transition">
                    <td class="py-3.5 px-6 font-mono font-bold text-slate-900">#<?php echo $escape($order['order_id']); ?></td>
                    <td class="py-3.5 px-6 font-semibold text-slate-800"><?php echo $escape($order['ordered_date_time']); ?></td>
                    <td class="py-3.5 px-6 text-slate-600"><?php echo $escape($order['company_name']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-600"><?php echo $escape($order['invoice_number'] ?: 'Ã¢â‚¬â€'); ?></td>
                    <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900"><?php echo number_format((float) $order['total_quantity'], 2); ?> kg</td>
                    <td class="py-3.5 px-6 text-center">
                      <?php if ($order['receipt_status'] === 'Pending'): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Pending</span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Completed</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3.5 px-6 text-right">
                      <?php if ($order['receipt_status'] === 'Pending'): ?>
                        <button type="button" onclick="document.getElementById('order-edit-<?php echo (int) $order['order_id']; ?>').classList.remove('hidden')" class="text-blue-600 hover:text-red-600 font-bold text-xs">Edit</button>
                      <?php else: ?>
                        <button type="button" onclick="document.getElementById('order-detail-<?php echo (int) $order['order_id']; ?>').classList.remove('hidden')" class="text-blue-600 hover:text-red-600 font-bold text-xs">View</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

            </tbody>
          </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $displayStart; ?>-<?php echo $displayEnd; ?></span> of <span class="font-bold text-slate-800"><?php echo $totalRecords; ?></span> log entries</span>

          <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars($pageUrl(1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="First Page" aria-label="First page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="First Page" aria-label="First page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
            <?php if ($currentPage > 1): ?></a><?php else: ?></button><?php endif; ?>

            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($currentPage - 1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Page" aria-label="Previous page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Previous Page" aria-label="Previous page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            <?php if ($currentPage > 1): ?></a><?php else: ?></button><?php endif; ?>

            <span class="px-3 font-semibold text-slate-700">Records <?php echo $displayStart; ?>-<?php echo $displayEnd; ?> of <?php echo $totalRecords; ?></span>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($currentPage + 1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Next Page" aria-label="Next page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Next Page" aria-label="Next page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <?php if ($currentPage < $totalPages): ?></a><?php else: ?></button><?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($totalPages)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Last Page" aria-label="Last page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Last Page" aria-label="Last page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
            <?php if ($currentPage < $totalPages): ?></a><?php else: ?></button><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php foreach ($orders as $order): ?>
      <?php $orderId = (int) $order['order_id']; ?>
      <div id="order-detail-<?php echo $orderId; ?>" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden" onclick="if (event.target === this) this.classList.add('hidden')">
        <section class="bg-white w-full max-w-2xl max-h-[85vh] rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
          <header class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div><h2 class="text-base font-bold text-slate-900">Order #<?php echo $orderId; ?></h2><p class="text-[10px] text-slate-500">Purchase order details</p></div>
            <button type="button" onclick="document.getElementById('order-detail-<?php echo $orderId; ?>').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 text-xl" aria-label="Close">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </header>
          <div class="p-6 space-y-4 text-xs overflow-y-auto">
            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Supplier</span><span class="font-bold text-slate-800"><?php echo $escape($order['company_name']); ?></span></div>
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Order Date</span><span class="font-mono font-bold text-slate-800"><?php echo $escape($order['ordered_date_time']); ?></span></div>
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Expected Arrival</span><span class="font-mono font-bold text-slate-800"><?php echo $escape($order['expected_arrival'] ?? 'Not specified'); ?></span></div>
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Invoice Number</span><span class="font-mono font-bold text-slate-800"><?php echo $escape($order['invoice_number'] ?: 'Not specified'); ?></span></div>
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Transport</span><span class="font-bold text-slate-800"><?php echo $escape($order['transport_name'] ?: 'Not received'); ?></span></div>
              <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Ordered By</span><span class="font-bold text-slate-800"><?php echo $escape($order['full_name']); ?></span></div>
            </div>

            <div>
              <h3 class="block font-bold text-slate-700 mb-1">Ingredients &amp; Quantities</h3>
              <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left">
                  <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="px-3 py-2">Ingredient</th><th class="px-3 py-2 text-right">Ordered (kg)</th><th class="px-3 py-2 text-right">Received (kg)</th></tr></thead>
                  <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orderDetails[$orderId] ?? [] as $detail): ?>
                      <tr><td class="px-3 py-2 font-semibold text-slate-800"><?php echo $escape($detail['name']); ?></td><td class="px-3 py-2 text-right font-mono"><?php echo number_format((float) $detail['ordered_quantity_kgs'], 2); ?></td><td class="px-3 py-2 text-right font-mono"><?php echo number_format((float) $detail['received_quantity_kgs'], 2); ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($orderDetails[$orderId])): ?>
                      <tr><td colspan="3" class="px-3 py-3 text-center text-slate-500">No ingredient lines recorded.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <footer class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
            <button type="button" onclick="document.getElementById('order-detail-<?php echo $orderId; ?>').classList.add('hidden')" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition">Close Window</button>
          </footer>
        </section>
      </div>
    <?php endforeach; ?>

    <?php foreach ($orders as $order): ?>
      <?php
        $orderId = (int) $order['order_id'];
        if ($order['receipt_status'] !== 'Pending') {
          continue;
        }
        $orderDateParts = explode(' ', (string) $order['ordered_date_time']);
        $orderDateValue = $orderDateParts[0] ?? '';
        $orderTimeValue = isset($orderDateParts[1]) ? substr($orderDateParts[1], 0, 5) : '';
        $expectedArrivalValue = $order['expected_arrival'] ? substr((string) $order['expected_arrival'], 0, 10) : '';
        $pendingLines = array_values(array_filter($orderDetails[$orderId] ?? [], static fn ($detail): bool => !(bool) $detail['received_flag']));
        $receivedLines = array_values(array_filter($orderDetails[$orderId] ?? [], static fn ($detail): bool => (bool) $detail['received_flag']));
      ?>
      <div id="order-edit-<?php echo $orderId; ?>" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden" onclick="if (event.target === this) this.classList.add('hidden')">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
          <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div>
              <h2 class="text-base font-bold text-slate-900">Edit Order #<?php echo $orderId; ?></h2>
              <p class="text-[10px] text-slate-500">Update the schedule, supplier, and pending ingredient lines.</p>
            </div>
            <button type="button" onclick="document.getElementById('order-edit-<?php echo $orderId; ?>').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 text-xl" aria-label="Close">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>
          <form action="<?php echo htmlspecialchars(publicUrl('inventory/orders_update.php')); ?>" method="POST" class="flex-1 min-h-0 flex flex-col">
            <div class="p-6 pb-0 space-y-6 shrink-0">
              <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">

              <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Order Schedule</h2>
                <span class="text-xs text-slate-400">Status: <strong class="text-amber-600">Pending</strong></span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div>
                  <label class="block text-slate-700 font-bold mb-1">Order Date</label>
                  <input type="date" value="<?php echo $escape($orderDateValue); ?>" name="order_date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
                </div>
                <div>
                  <label class="block text-slate-700 font-bold mb-1">Order Time</label>
                  <input type="time" value="<?php echo $escape($orderTimeValue); ?>" name="order_time" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
                </div>
                <div>
                  <label class="block text-slate-700 font-bold mb-1">Expected Arrival Date</label>
                  <input type="date" value="<?php echo $escape($expectedArrivalValue); ?>" name="expected_arrival" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                  <label class="block text-slate-700 font-bold mb-1">Supplier Name</label>
                  <select name="supplier_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                      <option value="<?php echo (int) $supplier['supplier_id']; ?>" <?php echo $escape($order['company_name']) === $escape($supplier['company_name']) ? 'selected' : ''; ?>><?php echo $escape($supplier['company_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-slate-700 font-bold mb-1">Ordered By</label>
                  <select name="ordered_by_user_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
                    <option value="">-- Select Miller --</option>
                    <?php foreach ($millers as $miller): ?>
                      <option value="<?php echo (int) $miller['user_id']; ?>" <?php echo $escape($order['full_name']) === $escape($miller['full_name']) ? 'selected' : ''; ?>><?php echo $escape($miller['full_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex-1 min-h-0 flex flex-col">
                <div class="flex items-center justify-between mb-3 shrink-0">
                  <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Materials & Quantities Ordered</h2>
                  <button type="button" class="add-ingredient-row text-xs text-red-600 font-bold hover:underline">+ Add Line Item</button>
                </div>

                <div class="ingredient-rows flex-1 min-h-0 overflow-y-auto space-y-3 pr-1">
                  <?php foreach ($pendingLines as $detail): ?>
                    <div class="grid grid-cols-12 gap-3 items-end bg-slate-50 p-3 rounded-xl border border-slate-200">
                      <div class="col-span-7">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ingredient</label>
                        <select name="ingredients_id[]" required class="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs font-semibold text-slate-800">
                          <option value="">-- Select Ingredient --</option>
                          <?php foreach ($ingredients as $ingredient): ?>
                            <option value="<?php echo (int) $ingredient['ingredients_id']; ?>" <?php echo (int) $ingredient['ingredients_id'] === (int) $detail['ingredients_id'] ? 'selected' : ''; ?>><?php echo $escape($ingredient['name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-span-4">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Quantity Ordered</label>
                        <div class="relative">
                          <input type="number" min="0.01" max="999999.99" step="0.01" required value="<?php echo $escape(number_format((float) $detail['ordered_quantity_kgs'], 2, '.', '')); ?>" name="ordered_quantity_kgs[]" class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-10 py-2 text-xs font-mono font-bold text-right text-slate-900">
                          <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400">KGS</span>
                        </div>
                      </div>
                      <div class="col-span-1 text-center pb-1">
                        <button type="button" class="remove-row-btn text-slate-400 hover:text-red-600 transition p-1" aria-label="Remove ingredient">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <template class="ingredient-row-template">
                  <div class="grid grid-cols-12 gap-3 items-end bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="col-span-7">
                      <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ingredient</label>
                      <select name="ingredients_id[]" required class="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs font-semibold text-slate-800">
                        <option value="">-- Select Ingredient --</option>
                        <?php foreach ($ingredients as $ingredient): ?>
                          <option value="<?php echo (int) $ingredient['ingredients_id']; ?>"><?php echo $escape($ingredient['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-span-4">
                      <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Quantity Ordered</label>
                      <div class="relative">
                        <input type="number" min="0.01" max="999999.99" step="0.01" required name="ordered_quantity_kgs[]" class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-10 py-2 text-xs font-mono font-bold text-right text-slate-900">
                        <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400">KGS</span>
                      </div>
                    </div>
                    <div class="col-span-1 text-center pb-1">
                      <button type="button" class="remove-row-btn text-slate-400 hover:text-red-600 transition p-1" aria-label="Remove ingredient">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                      </button>
                    </div>
                  </div>
                </template>
            </div>

            <?php if ($receivedLines): ?>
              <div class="px-6 pt-4 border-t border-slate-100 shrink-0">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Already Received (locked)</h2>
                <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-32 overflow-y-auto">
                  <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="px-3 py-2">Ingredient</th><th class="px-3 py-2 text-right">Ordered (kg)</th><th class="px-3 py-2 text-right">Received (kg)</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                      <?php foreach ($receivedLines as $detail): ?>
                        <tr><td class="px-3 py-2 font-semibold text-slate-800"><?php echo $escape($detail['name']); ?></td><td class="px-3 py-2 text-right font-mono"><?php echo number_format((float) $detail['ordered_quantity_kgs'], 2); ?></td><td class="px-3 py-2 text-right font-mono"><?php echo number_format((float) $detail['received_quantity_kgs'], 2); ?></td></tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>

            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3 shrink-0">
              <button type="button" onclick="document.getElementById('order-edit-<?php echo $orderId; ?>').classList.add('hidden')" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition">Cancel</button>
              <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">Update Order</button>
            </div>
          </form>
        </div>
      </div>
    <?php endforeach; ?>

  </main>
  <script>
    document.querySelectorAll('.ingredient-rows').forEach((container) => {
      const template = container.nextElementSibling;
      const addButton = container.closest('form').querySelector('.add-ingredient-row');
      if (addButton) {
        addButton.addEventListener('click', () => {
          container.append(template.content.cloneNode(true));
        });
      }
      container.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-row-btn');
        if (removeButton && container.children.length > 1) {
          removeButton.closest('.grid').remove();
        }
      });
    });
  </script>
</body>
</html>
