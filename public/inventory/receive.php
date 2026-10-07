<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$pendingResult = $conn->query(
  "SELECT o.order_id, o.ordered_date_time, o.expected_arrival, s.company_name,
          SUM(oi.ordered_quantity_kgs) AS pending_quantity
   FROM v_order_ingredients oi
   INNER JOIN orders o ON o.order_id = oi.order_id
   LEFT JOIN suppliers s ON s.supplier_id = o.supplier_id
   WHERE COALESCE(oi.received_flag, b'0') = b'0'
   GROUP BY o.order_id, o.ordered_date_time, o.expected_arrival, s.company_name
   ORDER BY o.expected_arrival, o.order_id"
);
$pendingOrders = $pendingResult->fetch_all(MYSQLI_ASSOC);
$selectedOrderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$selectedOrder = null;
foreach ($pendingOrders as $pendingOrder) {
  if ((int) $pendingOrder['order_id'] === $selectedOrderId) {
    $selectedOrder = $pendingOrder;
    break;
  }
}
$pendingLines = [];
$orderReceipt = ['invoice_number' => '', 'received_transport_id' => ''];
if ($selectedOrder) {
  $linesStmt = $conn->prepare(
    "SELECT oi.ingredients_id, oi.name, oi.ordered_quantity_kgs
     FROM v_order_ingredients oi
     WHERE oi.order_id = ? AND COALESCE(oi.received_flag, b'0') = b'0'
     ORDER BY oi.name"
  );
  $linesStmt->bind_param('i', $selectedOrderId);
  $linesStmt->execute();
  $pendingLines = $linesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $linesStmt->close();

  $receiptStmt = $conn->prepare('SELECT invoice_number, received_transport_id FROM orders WHERE order_id = ?');
  $receiptStmt->bind_param('i', $selectedOrderId);
  $receiptStmt->execute();
  $orderReceipt = $receiptStmt->get_result()->fetch_assoc() ?: $orderReceipt;
  $receiptStmt->close();
}

$transports = $conn->query('SELECT transport_id, transport_name FROM transport ORDER BY transport_name')->fetch_all(MYSQLI_ASSOC);
$millers = $conn->query("SELECT user_id, full_name FROM millers WHERE active_flag = b'1' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Receive';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

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
          <span class="text-slate-600">Inventory</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Receive Orders</h1>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      <?php if (isset($_GET['success'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold">Receipt saved.</div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold">The receipt could not be saved. Check the selected order, transport, and quantities.</div>
      <?php elseif (isset($_GET['transport_saved'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold">Transport list saved.</div>
      <?php elseif (isset($_GET['transport_error'])): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold">Transport changes could not be saved. Check for duplicate names.</div>
      <?php endif; ?>

      <?php if (!$pendingOrders): ?>
        <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-8 text-center">
          <h2 class="text-sm font-bold text-slate-900">No pending orders</h2>
          <p class="mt-1 text-xs text-slate-500">Orders appear here when at least one ingredient has not been received.</p>
        </section>
      <?php else: ?>
      <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-3">
          <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Select Order to Receive</h2>
          <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">PENDING RECEIPT</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <form method="GET" class="sm:col-span-1">
            <label for="pending-order-select" class="block text-slate-500 font-medium mb-1">Pending Order</label>
            <select id="pending-order-select" name="order_id" onchange="this.form.submit()" class="w-full bg-slate-50 border border-red-300 font-mono font-bold text-red-600 rounded-xl p-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="" <?php echo !$selectedOrder ? 'selected' : ''; ?>>-- Select Order --</option>
              <?php foreach ($pendingOrders as $pendingOrder): ?>
                <option value="<?php echo (int) $pendingOrder['order_id']; ?>" <?php echo (int) $pendingOrder['order_id'] === $selectedOrderId ? 'selected' : ''; ?>>Order #<?php echo (int) $pendingOrder['order_id']; ?> - <?php echo $escape($pendingOrder['company_name'] ?: 'Supplier unavailable'); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($selectedOrder): ?>
            <div class="sm:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-[11px]">
              <div><span class="text-slate-400 block font-medium">Supplier</span><strong class="text-slate-800"><?php echo $escape($selectedOrder['company_name'] ?: 'Supplier unavailable'); ?></strong></div>
              <div><span class="text-slate-400 block font-medium">Order Date</span><strong class="text-slate-800 font-mono"><?php echo $escape($selectedOrder['ordered_date_time']); ?></strong></div>
              <div><span class="text-slate-400 block font-medium">Pending Quantity</span><strong class="text-slate-900 font-mono"><?php echo number_format((float) $selectedOrder['pending_quantity'], 2); ?> kg</strong></div>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($selectedOrder): ?>
      <!-- ACTUAL RECEIVING ENTRY FORM -->
      <form action="receive_save.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <input type="hidden" name="order_id" value="<?php echo (int) $selectedOrderId; ?>">
        
        <div class="border-b border-slate-100 pb-3">
          <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Shipment Arrival Log</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Arrival Date</label>
            <input type="date" value="<?php echo date('Y-m-d'); ?>" name="arrival_date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Arrival Time</label>
            <input type="time" value="<?php echo date('H:i'); ?>" name="arrival_time" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Received By</label>
            <select name="miller_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Miller --</option>
              <?php foreach ($millers as $miller): ?>
                <option value="<?php echo (int) $miller['user_id']; ?>"><?php echo $escape($miller['full_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="received-transport" class="block text-slate-700 font-bold mb-1">Transport</label>
              <button type="button" onclick="document.getElementById('transport-modal').classList.remove('hidden')" class="text-blue-700 hover:text-red-600 font-bold">[Edit]</button>
            </div>
            <select id="received-transport" name="transport_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Transport --</option>
              <?php foreach ($transports as $transport): ?>
                <option value="<?php echo $escape($transport['transport_id']); ?>" <?php echo ($transport['transport_id'] === ($orderReceipt['received_transport_id'] ?? '') || $transport['transport_id'] === ($_GET['transport_id'] ?? '')) ? 'selected' : ''; ?>><?php echo $escape($transport['transport_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Invoice / Delivery Note Number</label>
            <input type="text" maxlength="50" value="<?php echo $escape($orderReceipt['invoice_number']); ?>" name="invoice_number" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <!-- Material Quantities Received Breakdown -->
        <div class="pt-2">
          <label class="block text-xs font-bold text-slate-700 mb-3 uppercase tracking-wider">Quantities Received vs Ordered</label>
          
          <div class="space-y-3">
            <?php foreach ($pendingLines as $line): ?>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <label class="flex items-start gap-3 flex-1 min-w-56">
                  <input type="checkbox" name="receive_ingredients[]" value="<?php echo (int) $line['ingredients_id']; ?>" checked class="receive-line-checkbox mt-0.5 w-4 h-4 rounded text-red-600 focus:ring-red-600">
                  <span><strong class="text-xs text-slate-900 block"><?php echo $escape($line['name']); ?></strong><span class="text-[10px] text-slate-500">Ordered: <strong class="font-mono text-slate-700"><?php echo number_format((float) $line['ordered_quantity_kgs'], 2); ?> kg</strong></span></span>
                </label>
                <div class="relative w-44">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Received Quantity</label>
                  <input type="number" min="0" max="999999.99" step="0.01" value="<?php echo $escape($line['ordered_quantity_kgs']); ?>" name="received_quantities[<?php echo (int) $line['ingredients_id']; ?>]" class="receive-line-quantity w-full bg-white border border-slate-300 rounded-lg pl-2 pr-12 py-2 text-right font-mono text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-600 outline-none">
                  <span class="absolute right-2 bottom-2 text-[10px] font-bold text-slate-400">KGS</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <span class="text-xs text-slate-500">Uncheck any line that is not part of this delivery. It will remain pending.</span>
          </label>

          <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">Save Receiving Entry</button>
        </div>
      </form>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- ================= TRANSPORT CATALOG MODAL ================= -->
    <div id="transport-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden" onclick="if (event.target === this) this.classList.add('hidden')">
      <section class="bg-white w-full max-w-2xl max-h-[78vh] rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
        <header class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div><h2 class="text-base font-bold text-slate-900">Transport Catalog</h2><p class="text-[10px] text-slate-500">Add a transport or update its display name.</p></div>
          <button type="button" onclick="document.getElementById('transport-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700 text-xl" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </header>
        <div class="p-5 flex flex-1 min-h-0 flex-col gap-4 text-xs overflow-hidden">
          <form action="transport_save.php" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
            <input type="hidden" name="order_id" value="<?php echo (int) ($selectedOrderId ?? 0); ?>">
            <div class="flex-1">
              <label for="new-transport-name" class="block font-bold text-slate-700 mb-1">New Transport Name</label>
              <input id="new-transport-name" type="text" name="transport_name" maxlength="100" required class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl">Add Transport</button>
          </form>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50 border-b border-slate-200 p-3 rounded-xl">
            <label for="transport-catalog-search" class="font-bold text-slate-700">Find a transport</label>
            <input type="search" id="transport-catalog-search" placeholder="Search by name or ID" class="w-full sm:w-72 bg-white border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <form id="transport-update-form" action="transport_update.php" method="POST" class="flex flex-1 min-h-0 flex-col">
            <input type="hidden" name="order_id" value="<?php echo (int) ($selectedOrderId ?? 0); ?>">
            <div class="flex-1 min-h-0 overflow-x-auto overflow-y-auto border border-slate-200 rounded-xl">
              <table class="w-full text-left border-collapse">
                <thead><tr class="sticky top-0 z-10 bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200"><th class="py-3 px-4 w-40">ID</th><th class="py-3 px-4">Transport Name</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                  <?php if (!$transports): ?>
                    <tr><td colspan="2" class="py-6 px-4 text-center text-slate-500">No transports have been added.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($transports as $transport): ?>
                    <tr class="transport-catalog-row">
                      <td class="py-2 px-4 font-mono text-slate-500"><input type="hidden" name="transport_ids[]" value="<?php echo $escape($transport['transport_id']); ?>"><?php echo $escape($transport['transport_id']); ?></td>
                      <td class="py-2 px-4"><input type="text" name="transport_names[]" value="<?php echo $escape($transport['transport_name']); ?>" maxlength="100" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 font-medium focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600"></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p id="transport-catalog-count" class="pt-2 text-[10px] text-slate-500" aria-live="polite">Showing <?php echo count($transports); ?> of <?php echo count($transports); ?> transports</p>
          </form>
        </div>
        <footer class="p-3 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('transport-modal').classList.add('hidden')" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition">Cancel</button>
          <?php if ($transports): ?>
            <button type="submit" form="transport-update-form" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition">Save Changes</button>
          <?php endif; ?>
        </footer>
      </section>
    </div>
  </main>
  <script>
    const transportSearch = document.getElementById('transport-catalog-search');
    const transportRows = [...document.querySelectorAll('.transport-catalog-row')];
    const transportCount = document.getElementById('transport-catalog-count');
    transportSearch?.addEventListener('input', () => {
      const term = transportSearch.value.trim().toLowerCase();
      let visibleCount = 0;
      transportRows.forEach((row) => {
        const name = row.querySelector('input[name="transport_names[]"]').value;
        const id = row.querySelector('input[name="transport_ids[]"]').value;
        const matches = `${name} ${id}`.toLowerCase().includes(term);
        row.hidden = !matches;
        if (matches) visibleCount += 1;
      });
      if (transportCount) transportCount.textContent = `Showing ${visibleCount} of ${transportRows.length} transports`;
    });

    document.querySelectorAll('.receive-line-checkbox').forEach((checkbox) => {
      const quantity = checkbox.closest('.p-3').querySelector('.receive-line-quantity');
      const syncQuantity = () => {
        quantity.disabled = !checkbox.checked;
        quantity.required = checkbox.checked;
      };
      checkbox.addEventListener('change', syncQuantity);
      syncQuantity();
    });
  </script>
</body>
</html>
