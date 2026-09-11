<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
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
    <div class="h-1.5 bg-red-600 w-full"></div>

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs">
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
      
      <!-- ASSOCIATED ORDER REFERENCE SECTION -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Select Order to Receive</h2>
          </div>
          <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">STATUS: PENDING RECEIPT</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div class="sm:col-span-1">
            <label class="block text-slate-400 font-medium mb-1">Select Pending Order ID</label>
            <select name="select_order_id" class="w-full bg-slate-50 border border-red-300 font-mono font-bold text-red-600 rounded-xl p-2.5 text-xs focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="B0848" selected>Order #B0848 Ã¢â‚¬â€ ADM Grain Co.</option>
              <option value="B0847">Order #B0847 Ã¢â‚¬â€ Cargill Caribbean</option>
            </select>
          </div>

          <!-- Associated Order Summary Card -->
          <div class="sm:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-[11px]">
            <div>
              <span class="text-slate-400 block font-medium">Supplier:</span>
              <strong class="text-slate-800">ADM Grain Co. Ltd.</strong>
            </div>
            <div>
              <span class="text-slate-400 block font-medium">Order Date:</span>
              <strong class="text-slate-800 font-mono">8/5/2026 @ 14:51</strong>
            </div>
            <div>
              <span class="text-slate-400 block font-medium">Ordered Weight:</span>
              <strong class="text-slate-900 font-mono">20,700.00 KGS</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTUAL RECEIVING ENTRY FORM -->
      <form action="" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        
        <div class="border-b border-slate-100 pb-3">
          <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Shipment Arrival Log</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Arrival Date</label>
            <input type="date" value="2026-08-21" name="arrival_date" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Arrival Time</label>
            <input type="time" value="11:15" name="arrival_time" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Miller / Receiving Inspector</label>
            <select name="miller_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Inspector --</option>
              <option value="1" selected>R. Thomas (Shift A)</option>
              <option value="2">M. Charles (Shift B)</option>
            </select>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-slate-700 font-bold mb-1">Name of Vessel / Boat</label>
              <button onclick="document.getElementById('vesselModal').classList.remove('hidden')" class="text-slate-400 block font-small">[Edit]</span>   
            </div>
            
            <select name="boat_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Boat --</option>
              <option value="carib_star" selected>M/V Caribbean Star</option>
              <option value="island_trader">M/V Island Trader</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Invoice / Delivery Note No.</label>
            <input type="text" placeholder="e.g. INV-2026-881" name="invoice_no" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <!-- Material Quantities Received Breakdown -->
        <div class="pt-2">
          <label class="block text-xs font-bold text-slate-700 mb-3 uppercase tracking-wider">Quantities Received vs Ordered</label>
          
          <div class="space-y-3">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
              <div>
                <span class="text-xs font-bold text-slate-900 block">Yellow Corn Bulk</span>
                <span class="text-[10px] text-slate-400">Ordered: <strong class="text-slate-600 font-mono">12,500.00 KGS</strong></span>
              </div>
              <div class="relative w-40">
                <input type="number" step="0.01" value="12500.00" name="qty_received[]" class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-10 py-2 text-right font-mono text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-600 outline-none">
                <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400">KGS</span>
              </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
              <div>
                <span class="text-xs font-bold text-slate-900 block">Soybean Meal Bulk</span>
                <span class="text-[10px] text-slate-400">Ordered: <strong class="text-slate-600 font-mono">8,200.00 KGS</strong></span>
              </div>
              <div class="relative w-40">
                <input type="number" step="0.01" value="8200.00" name="qty_received[]" class="w-full bg-white border border-slate-300 rounded-lg pl-2 pr-10 py-2 text-right font-mono text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-600 outline-none">
                <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400">KGS</span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_received" class="w-4 h-4 rounded text-red-600 focus:ring-red-600" checked>
            <span class="text-xs font-bold text-slate-800">Mark Order #B0848 as Fully Received</span>
          </label>

          <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">Save Receiving Entry</button>
        </div>
      </form>
    </div>

    <!-- Vessel Management Modal -->
    <div id="vesselModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden">
      <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md mx-4 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider">Manage Vessels / Boats</h3>
          <button type="button" onclick="document.getElementById('vesselModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <div class="p-5 space-y-4">
          <!-- Add New Vessel Form -->
          <form action="" method="POST" class="flex gap-2 border-b border-slate-100 pb-4">
            <input type="hidden" name="action" value="add">
            <input type="text" name="vessel_name" placeholder="New vessel name..." required class="flex-1 bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
            <button type="submit" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition">+ Add</button>
          </form>

          <!-- Vessel List (Edit In-Place) -->
          <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            
            <!-- Example Row 1 -->
            <form action="" method="POST" class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="vessel_id" value="1">
              <input type="text" name="vessel_name" value="M/V Caribbean Star" required class="flex-1 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-600 outline-none">
              <button type="submit" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-[11px] font-bold rounded-lg transition">Save</button>
            </form>

            <!-- Example Row 2 -->
            <form action="" method="POST" class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="vessel_id" value="2">
              <input type="text" name="vessel_name" value="M/V Island Trader" required class="flex-1 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-red-600 outline-none">
              <button type="submit" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-[11px] font-bold rounded-lg transition">Save</button>
            </form>

          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3 bg-slate-50 border-t border-slate-100 flex justify-end">
          <button type="button" onclick="document.getElementById('vesselModal').classList.add('hidden')" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition">Done</button>
        </div>

      </div>
    </div>
  </main>

</body>
</html>
