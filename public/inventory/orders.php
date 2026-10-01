<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$nextOrderResult = $conn->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS next_order_id FROM orders');
$nextOrderId = (int) $nextOrderResult->fetch_assoc()['next_order_id'];
$suppliers = $conn->query('SELECT supplier_id, company_name FROM suppliers ORDER BY company_name')->fetch_all(MYSQLI_ASSOC);
$millers = $conn->query("SELECT user_id, full_name FROM millers WHERE active_flag = b'1' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$ingredients = $conn->query("SELECT ingredients_id, name FROM ingredients WHERE active_flag = b'1' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
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

      <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-mono">
        <span class="text-slate-400">Order ID:</span>
        <span class="font-bold text-red-600">#<?php echo $nextOrderId; ?></span>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      <?php if (isset($_GET['success'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold">Order created successfully.</div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold">The order could not be saved. Check the entered values and try again.</div>
      <?php endif; ?>
      <form action="orders_save.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        
        <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
          <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Order Schedule</h2>
          <span class="text-xs text-slate-400">Status: <strong class="text-amber-600">Draft Order</strong></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Order Date</label>
            <input type="date" value="<?php echo date('Y-m-d'); ?>" name="order_date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Order Time</label>
            <input type="time" value="<?php echo date('H:i'); ?>" name="order_time" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Expected Arrival Date</label>
            <input type="date" name="expected_arrival" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Supplier Name</label>
            <select name="supplier_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Supplier --</option>
              <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo (int) $supplier['supplier_id']; ?>"><?php echo $escape($supplier['company_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 font-bold mb-1">Ordered By</label>
            <select name="ordered_by_user_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600 outline-none cursor-pointer">
              <option value="">-- Select Miller --</option>
              <?php foreach ($millers as $miller): ?>
                <option value="<?php echo (int) $miller['user_id']; ?>"><?php echo $escape($miller['full_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Materials & Quantities Ordered</h2>
            <button type="button" id="add-ingredient-row" class="text-xs text-red-600 font-bold hover:underline">+ Add Line Item</button>
          </div>

          <div id="ingredient-rows" class="space-y-3"></div>
          <template id="ingredient-row-template">
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
        <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">          
          <button type="reset" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition">Clear</button>          
          <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">Create Order</button>        
        </div>      
      </form>    
    </div> 
  </main>
  <script>
    const ingredientRows = document.getElementById('ingredient-rows');
    const ingredientRowTemplate = document.getElementById('ingredient-row-template');
    document.getElementById('add-ingredient-row').addEventListener('click', () => {
      ingredientRows.append(ingredientRowTemplate.content.cloneNode(true));
    });
    ingredientRows.addEventListener('click', (event) => {
      const removeButton = event.target.closest('.remove-row-btn');
      if (removeButton && ingredientRows.children.length > 1) {
        removeButton.closest('.grid').remove();
      }
    });
    ingredientRows.append(ingredientRowTemplate.content.cloneNode(true));
  </script>
</body>
</html>
