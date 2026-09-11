<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$nextSaleId = 1;
$saleIdResult = $conn->query('SELECT COALESCE(MAX(sale_id), 0) + 1 AS next_sale_id FROM ingredients_sold_separately_view');
if ($saleIdResult && ($saleIdRow = $saleIdResult->fetch_assoc())) {
  $nextSaleId = (int) $saleIdRow['next_sale_id'];
}

$ingredients = [];
$ingredientResult = $conn->query('SELECT ingredients_id, ingredients FROM ingredients_sold_separately_view WHERE ingredients_id IS NOT NULL AND ingredients IS NOT NULL GROUP BY ingredients_id, ingredients ORDER BY ingredients ASC');
if ($ingredientResult) {
  while ($row = $ingredientResult->fetch_assoc()) {
    $ingredients[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Items Sold Separately';
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
          <span class="text-slate-600">Production</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Items Sold Separately</h1>
      </div>

      <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-mono">
        <span class="text-slate-400">Sale ID:</span>
        <span class="font-bold text-red-600">#<?php echo $nextSaleId; ?></span>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-4xl space-y-6">
      
      <form action="items_save.php" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        
        <!-- Top Section: Metadata -->
        <div class="border-b border-slate-100 pb-4 flex flex-wrap items-center justify-between gap-4">
          <h2 class="text-xs font-bold uppercase tracking-wider text-red-600">Production & Sales Info</h2>
          <span class="text-[11px] font-bold text-slate-400">RECORD ENTRY</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <label class="block text-slate-700 font-bold mb-1">Date</label>
            <input type="date" value="<?php echo date('Y-m-d'); ?>" name="entry_date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>
          
          <div>
            <label class="block text-slate-700 font-bold mb-1">Ingredient</label>
            <select name="ingredients_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600 cursor-pointer">
              <option value="">-- Select Ingredient --</option>
              <?php foreach ($ingredients as $ingredient): ?>
                <option value="<?php echo (int) $ingredient['ingredients_id']; ?>"><?php echo htmlspecialchars($ingredient['ingredients']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-slate-700 font-bold mb-1">Total Quantity</label>
            <div class="relative">
              <input type="number" min="0" max="32767" step="1" name="quantity_kgs" required class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-3 pr-12 py-2.5 font-mono font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              <span class="absolute right-3 top-3 text-[10px] font-bold text-slate-400">KGS</span>
            </div>
          </div>
        </div>

        <hr class="border-slate-100">

        <!-- Sales Destination Options -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Sales Destination Option</label>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition">
              <input type="radio" name="sale_option" value="local" class="w-4 h-4 text-red-600 focus:ring-red-600" checked>
              <div>
                <span class="block text-xs font-bold text-slate-800">Sold (Local)</span>
                <span class="block text-[10px] text-slate-400">Domestic distribution</span>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition">
              <input type="radio" name="sale_option" value="overseas" class="w-4 h-4 text-red-600 focus:ring-red-600">
              <div>
                <span class="block text-xs font-bold text-slate-800">Sold (Overseas)</span>
                <span class="block text-[10px] text-slate-400">Regional / Export shipment</span>
              </div>
            </label>
          </div>

          <!-- Total Quantity Output -->
        </div>

        <!-- Action Controls Toolbar -->
        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <button type="reset" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl transition border border-rose-200">
              Undo
            </button>
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition">
              Save Entry
            </button>
          </div>
        </div>

      </form>

    </div>
  </main>

</body>
</html>
