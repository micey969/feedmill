<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Orders';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

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
          <span class="text-slate-600">Inventory</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Purchase Orders</h1>
      </div>

      <div class="flex items-center gap-3">
        <div class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-mono">
          <span class="text-slate-400">New Order ID:</span>
          <span class="font-bold text-red-600 ml-1">#B0849</span>
        </div>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      <form action="save_order.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        
        <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Supplier & Order Schedule</h2>
          <span class="text-xs text-slate-400">Status: <strong class="text-amber-600">Draft Order</strong></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Order Date</label>
            <input type="date" value="2026-08-21" name="order_date" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Order Time</label>
            <input type="time" value="11:00" name="order_time" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Expected Arrival Date</label>
            <input type="date" value="2026-08-28" name="expected_arrival" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Supplier Name</label>
            <select name="supplier_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Supplier --</option>
              <option value="adm" selected>ADM Grain Co. Ltd.</option>
              <option value="cargill">Cargill Caribbean</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Contact Person</label>
            <input type="text" placeholder="e.g. John Miller" name="contact_person" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Contact Position</label>
            <input type="text" placeholder="e.g. Logistics Officer" name="contact_position" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Materials & Quantities Ordered</h2>
            <button type="button" class="text-xs text-red-600 font-bold hover:underline">+ Add Line Item</button>
          </div>

          <div class="space-y-3">
            <div class="grid grid-cols-12 gap-3 items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
              <div class="col-span-7">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Bulk Material</label>
                <select name="material_id[]" class="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs font-semibold text-slate-800">
                  <option value="1" selected>Yellow Corn Bulk</option>
                  <option value="2">Soybean Meal Bulk</option>
                </select>
              </div>
              <div class="col-span-4">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Weight Ordered</label>
                <div class="relative">
                  <input type="number" step="0.01" value="12500.00" name="ordered_weight[]" class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-10 py-2 text-xs font-mono font-bold text-right text-slate-900">
                  <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400">KGS</span>
                </div>
              </div>
              <div class="col-span-1 text-center pt-4">
                <button type="button" class="text-slate-400 hover:text-red-600 font-bold text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
          <button type="reset" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">Clear</button>
          <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">Create & Submit Order</button>
        </div>
      </form>
    </div>
  </main>

</body>
</html>
