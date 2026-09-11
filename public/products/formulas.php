<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$ingredientOptions = [];
$ingredientResult = $conn->query('SELECT ingredients_id, name FROM ingredients WHERE active_flag = 1 ORDER BY name ASC');
if ($ingredientResult) {
  $ingredientOptions = $ingredientResult->fetch_all(MYSQLI_ASSOC);
}
$catalogResult = $conn->query('SELECT ingredients_id, name, active_flag FROM ingredients ORDER BY active_flag DESC, name ASC');
$ingredientCatalog = $catalogResult ? $catalogResult->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Formulas';
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
          <span class="text-slate-600">Product Management</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Formulas</h1>
      </div>

      <!-- Header Action Group -->
      <div class="flex items-center gap-2">
        <button onclick="openMaterialModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          <span>Manage Ingredients</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <form action="formula_save.php" method="POST" class="p-6 sm:p-8 max-w-6xl space-y-6">
      
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- ================= LEFT COLUMN: FORMULA METADATA CARD ================= -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Formula Details</h2>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">New Draft</span>
          </div>

          <!-- Feed Code -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Feed Code *</label>
            <input type="text" name="formula_id" placeholder="TU-ST-RB06" required
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Sold As -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Sold As *</label>
            <input type="text" name="formula_name" placeholder="TURKEY STARTER" required
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Creator -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Creator</label>
            <input type="text" name="creator" placeholder="Enter creator name"
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Description -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Description</label>
            <textarea name="description" rows="2" placeholder="e.g. Starter feed for turkey chicks aged 0-4 weeks"
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600"></textarea>
          </div>

          <!-- Date -->
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Effective Date</label>
            <input type="date" name="setup_date" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>" required
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Activating Formula Toggle -->
          <div class="pt-2 border-t border-slate-100">
            <label class="flex items-center justify-between cursor-pointer p-3 bg-slate-50 rounded-xl border border-slate-200">
              <div>
                <span class="text-xs font-bold text-slate-800 block">Activating Formula</span>
                <span class="text-[10px] text-slate-500">Set as active production baseline</span>
              </div>
              <input type="hidden" name="active_flag" value="0">
              <input type="checkbox" name="active_flag" value="1" checked class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-600">
            </label>
          </div>
        </div>

        <!-- ================= RIGHT COLUMN: INGREDIENTS ORDER-FORM CARD ================= -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Formula Ingredients</h2>
              <p class="text-[11px] text-slate-500">Add materials and set target quantities per batch.</p>
            </div>
            <button type="button" id="add-row-btn" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-xl border border-red-200 transition flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
              <span>Add Item</span>
            </button>
          </div>

          <!-- Ingredients Order Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse" id="ingredients-table">
              <thead>
                <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                  <th class="py-2.5 px-3">Raw Material</th>
                  <th class="py-2.5 px-3 text-right w-36">Quantity (Kg)</th>
                  <th class="py-2.5 px-3 text-center w-12"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100" id="ingredients-list">
                
                <!-- Row 1 -->
                <tr>
                  <td class="py-2 px-3">
                    <select name="ingredients[]" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 font-medium focus:bg-white focus:ring-1 focus:ring-red-600">
                      <option value="">Select an active ingredient</option>
                      <?php foreach ($ingredientOptions as $ingredient): ?>
                        <option value="<?php echo (int) $ingredient['ingredients_id']; ?>"><?php echo htmlspecialchars($ingredient['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="py-2 px-3">
                    <input type="number" step="0.1" name="quantities[]" value="" class="qty-input w-full text-right bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 font-mono text-xs font-semibold focus:bg-white focus:ring-1 focus:ring-red-600">
                  </td>
                  <td class="py-2 px-3 text-center">
                    <button type="button" class="remove-row-btn text-slate-400 hover:text-red-600 transition p-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Total Calculation Footer -->
          <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex items-center justify-between text-xs font-bold text-slate-800">
            <span>Total Batch Weight:</span>
            <span class="font-mono text-base text-red-600" id="total-weight-display">0.0 Kg</span>
          </div>
        </div>

      </div>

      <!-- Action Toolbar Footer -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <button type="reset" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition">Undo</button>
        <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <span>Save Formula</span>
        </button>
      </div>
    </form>
    <!-- ================= INGREDIENT CATALOG MODAL ================= -->
    <div id="material-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-2xl max-h-[78vh] rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
        
        <!-- Modal Header -->
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Ingredient Catalog</h2>
            <p class="text-[10px] text-slate-500">Add ingredients or update their names and active status.</p>
          </div>
          <button onclick="closeMaterialModal()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <div class="p-5 space-y-4 text-xs overflow-y-auto">
          <form action="ingredient_save.php" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
            <div class="flex-1">
              <label for="ingredient-name" class="block font-bold text-slate-700 mb-1">New Ingredient Name *</label>
              <input type="text" id="ingredient-name" name="name" required placeholder="e.g. Wheat Middlings" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 font-medium focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition">Add Ingredient</button>
          </form>

          <form action="ingredient_update.php" method="POST">
            <div class="mb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50 border-b border-slate-200 p-3 rounded-xl">
              <label for="ingredient-catalog-search" class="font-bold text-slate-700">Find an ingredient</label>
              <div class="relative w-full sm:w-72">
                <input type="search" id="ingredient-catalog-search" placeholder="Search by name or ID"
                  class="w-full bg-white border border-slate-300 rounded-lg pl-3 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
            </div>
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 px-4 w-24">ID</th>
                    <th class="py-3 px-4">Ingredient Name</th>
                    <th class="py-3 px-4 w-40">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php if (!$ingredientCatalog): ?>
                    <tr><td colspan="3" class="py-6 px-4 text-center text-slate-500">No ingredients have been added.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($ingredientCatalog as $ingredient): ?>
                    <tr class="ingredient-catalog-row">
                      <td class="py-2 px-4 font-mono text-slate-500"><?php echo (int) $ingredient['ingredients_id']; ?></td>
                      <td class="py-2 px-4">
                        <input type="hidden" name="ids[]" value="<?php echo (int) $ingredient['ingredients_id']; ?>">
                        <input type="text" name="names[]" value="<?php echo htmlspecialchars($ingredient['name']); ?>" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 font-medium focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600">
                      </td>
                      <td class="py-2 px-4">
                        <select name="active_flags[]" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2 py-1.5 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600">
                          <option value="1" <?php echo (int) $ingredient['active_flag'] === 1 ? 'selected' : ''; ?>>Active</option>
                          <option value="0" <?php echo (int) $ingredient['active_flag'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($ingredientCatalog): ?>
              <p id="ingredient-catalog-count" class="pt-2 text-[10px] text-slate-500" aria-live="polite">
                Showing <?php echo count($ingredientCatalog); ?> of <?php echo count($ingredientCatalog); ?> ingredients
              </p>
            <?php endif; ?>
            <?php if ($ingredientCatalog): ?>
              <div class="pt-4 flex justify-end">
                <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition">Save Changes</button>
              </div>
            <?php endif; ?>
          </form>
      </div>
    </div>
  </main>

</body>
<script>
  const ingredientsList = document.getElementById('ingredients-list');
  const addRowButton = document.getElementById('add-row-btn');
  const totalWeightDisplay = document.getElementById('total-weight-display');
  const formulaForm = document.querySelector('form[action="formula_save.php"]');
  const catalogSearch = document.getElementById('ingredient-catalog-search');
  const catalogRows = [...document.querySelectorAll('.ingredient-catalog-row')];
  const catalogCount = document.getElementById('ingredient-catalog-count');

  function openMaterialModal() {
    document.getElementById('material-modal').classList.remove('hidden');
  }

  function closeMaterialModal() {
    document.getElementById('material-modal').classList.add('hidden');
  }

  function filterIngredientCatalog() {
    const searchTerm = catalogSearch.value.trim().toLowerCase();
    let visibleCount = 0;

    catalogRows.forEach((row) => {
      const ingredientName = row.querySelector('input[name="names[]"]')?.value || '';
      const searchableText = `${row.textContent} ${ingredientName}`.toLowerCase();
      const matches = searchableText.includes(searchTerm);
      row.hidden = !matches;
      if (matches) visibleCount += 1;
    });

    if (catalogCount) {
      catalogCount.textContent = `Showing ${visibleCount} of ${catalogRows.length} ingredients`;
    }
  }

  function updateTotalWeight() {
    const total = [...document.querySelectorAll('.qty-input')]
      .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    totalWeightDisplay.textContent = `${total.toFixed(1)} Kg`;
  }

  function addIngredientRow() {
    const row = ingredientsList.querySelector('tr').cloneNode(true);
    row.querySelector('select').selectedIndex = 0;
    row.querySelector('.qty-input').value = '';
    ingredientsList.appendChild(row);
    updateTotalWeight();
  }

  addRowButton.addEventListener('click', addIngredientRow);
  ingredientsList.addEventListener('click', (event) => {
    const removeButton = event.target.closest('.remove-row-btn');
    if (!removeButton) return;
    const rows = ingredientsList.querySelectorAll('tr');
    if (rows.length > 1) removeButton.closest('tr').remove();
    updateTotalWeight();
  });
  ingredientsList.addEventListener('input', updateTotalWeight);
  formulaForm.addEventListener('reset', () => setTimeout(updateTotalWeight, 0));
  catalogSearch?.addEventListener('input', filterIngredientCatalog);
  updateTotalWeight();
</script>
</html>
