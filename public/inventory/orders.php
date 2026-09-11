<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Purchase Orders';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    
    <!-- Red Header Accent Line -->
    <div class="h-1.5 bg-red-600 w-full"></div>

    <!-- Page Header Bar -->
    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="#" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Inventory</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Purchase Orders</h1>
      </div>

      <!-- Quick Search & Create Order Trigger -->
      <div class="flex items-center gap-3">
        <div class="relative w-64">
          <input type="text" placeholder="Search PO #, ingredient, supplier..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          <div class="absolute left-3 top-2.5">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          </div>
        </div>

        <button onclick="document.getElementById('add-bulk-order-modal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          <span>New Order</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl space-y-4">
      
      <!-- Helper Banner -->
      <div class="flex items-center justify-between bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl text-xs font-medium">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span>Manage raw bulk material purchase orders, vessel shipments, and tonnage allocations.</span>
        </div>
        <span class="font-bold text-blue-600 font-mono">3 Active Shipments</span>
      </div>

      <!-- Orders Data Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Order ID</th>
                <th class="py-3.5 px-6">OrderDate</th>
                <th class="py-3.5 px-6">Supplier</th>
                <th class="py-3.5 px-6 text-right">Total Weight</th>
                <th class="py-3.5 px-6 text-center">Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              
              <!-- Dummy Row 1 -->
              <tr onclick="document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="hover:bg-slate-50/80 transition cursor-pointer">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">BPO-2026-088</td>
                <td class="py-3.5 px-6 font-semibold text-slate-800">2023-10-15</td>
                <td class="py-3.5 px-6 text-slate-600">AgriTrade Grain Corp</td>
                <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">500.00 MT</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-100 text-emerald-700 border-emerald-200">
                    Delivered
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button type="button" onclick="event.stopPropagation(); document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                </td>
              </tr>

              <!-- Dummy Row 2 -->
              <tr onclick="document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="hover:bg-slate-50/80 transition cursor-pointer">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">BPO-2026-089</td>
                <td class="py-3.5 px-6 font-semibold text-slate-800">2023-10-15</td>
                <td class="py-3.5 px-6 text-slate-600">Caribbean Feed Ingredients</td>
                <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">250.00 MT</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-amber-100 text-amber-700 border-amber-200">
                    In Transit
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button type="button" onclick="event.stopPropagation(); document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                </td>
              </tr>

              <!-- Dummy Row 3 -->
              <tr onclick="document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="hover:bg-slate-50/80 transition cursor-pointer">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">BPO-2026-090</td>
                <td class="py-3.5 px-6 font-semibold text-slate-800">2023-10-15</td>
                <td class="py-3.5 px-6 text-slate-600">Maritime Milling Co.</td>
                <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">120.00 MT</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-blue-100 text-blue-700 border-blue-200">
                    Pending Wharfage
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button type="button" onclick="event.stopPropagation(); document.getElementById('edit-bulk-order-modal').classList.remove('hidden')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800">1-3</span> of <span class="font-bold text-slate-800">3</span> orders</span>

          <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <button type="button" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="First Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          </button>
          <button type="button" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </button>
          <span class="px-3 font-semibold text-slate-700">Record 17149 of 17149</span>
          <button type="button" class="px-2 py-1 text-slate-400 cursor-not-allowed" disabled title="Next Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </button>
          <button type="button" class="px-2 py-1 text-slate-400 cursor-not-allowed" disabled title="New Sheet Record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          </button>
        </div>
        </div>

      </div>

    </div>

    <!-- ================= ADD BULK ORDER MODAL ================= -->
    <div id="add-bulk-order-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Create Raw Material Order</h2>
            <p class="text-[10px] text-slate-500">Record a new purchase order for raw ingredients.</p>
          </div>
          <button onclick="document.getElementById('add-bulk-order-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <form action="order_save.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        
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
    </div>

    <!-- ================= EDIT BULK ORDER MODAL ================= -->
    <div id="edit-bulk-order-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Edit Raw Material Order</h2>
            <p class="text-[10px] text-slate-500">Update order status, vessel details, or total metric tonnage.</p>
          </div>
          <button type="button" onclick="document.getElementById('edit-bulk-order-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg" aria-label="Close">&times;</button>
        </div>
        <form action="order_update.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        
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
    </div>

  </main>
</body>
</html>