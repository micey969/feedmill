<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['search'] ?? ''));
$status = $_GET['status'] ?? 'all';
$status = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';
$view = 'v_formulas';
$like = "%$search%";
$where = '(formula_id LIKE ? OR formula_name LIKE ? OR description LIKE ? OR creator LIKE ?)';
$types = 'ssss';
$params = [$like, $like, $like, $like];

if ($status !== 'all') {
  $where .= ' AND formula_active_flag = ?';
  $types .= 'i';
  $params[] = $status === 'active' ? 1 : 0;
}

$count = $conn->prepare("SELECT COUNT(DISTINCT formula_id) AS total FROM `$view` WHERE $where");
$count->bind_param($types, ...$params);
$count->execute();
$total = (int) $count->get_result()->fetch_assoc()['total'];
$count->close();

$pages = max(1, (int) ceil($total / $limit));
$page = min($page, $pages);
$offset = ($page - 1) * $limit;
$list = $conn->prepare("SELECT formula_id, MAX(formula_name) AS formula_name, MAX(creator) AS creator, MAX(setup_date) AS setup_date, MAX(description) AS description, MAX(formula_active_flag) AS formula_active_flag FROM `$view` WHERE $where GROUP BY formula_id ORDER BY formula_active_flag DESC, formula_id ASC LIMIT ? OFFSET ?");
$listParams = array_merge($params, [$limit, $offset]);
$list->bind_param($types . 'ii', ...$listParams);
$list->execute();
$formulas = $list->get_result()->fetch_all(MYSQLI_ASSOC);
$list->close();

$ingredientsByFormula = [];
if ($formulas) {
  $formulaIds = array_column($formulas, 'formula_id');
  $placeholders = implode(',', array_fill(0, count($formulaIds), '?'));
  $items = $conn->prepare("SELECT formula_id, ingredients_id, ingredients, quantity_kgs FROM `$view` WHERE formula_id IN ($placeholders) ORDER BY formula_id, ingredients_id");
  $items->bind_param(str_repeat('s', count($formulaIds)), ...$formulaIds);
  $items->execute();
  foreach ($items->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
    $ingredientsByFormula[$item['formula_id']][] = $item;
  }
  $items->close();
}

$ingredientOptions = $conn->query('SELECT ingredients_id, name FROM ingredients ORDER BY active_flag DESC, name ASC')->fetch_all(MYSQLI_ASSOC);
foreach ($formulas as &$formula) {
  $formula['ingredients'] = $ingredientsByFormula[$formula['formula_id']] ?? [];
}
unset($formula);

$start = $total > 0 ? $offset + 1 : 0;
$end = min($offset + $limit, $total);

function feedListUrl(int $page, string $search, string $status): string {
  $query = http_build_query(array_filter([
    'page' => $page,
    'search' => $search,
    'status' => $status === 'all' ? null : $status,
  ], static fn ($value) => $value !== null && $value !== ''));
  return publicUrl('products/feedlist.php') . ($query !== '' ? '?' . $query : '');
}
?>
<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Formula List';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

<?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

<main class="flex-1 min-w-0 overflow-y-auto h-screen">
  
  <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>

  <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex items-center justify-between sticky top-1.5 z-10">
    <div>
      <nav class="flex gap-2 text-xs text-slate-400">
        <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600">Main Hub</a>
        <span>/</span>
        <span>Product Management</span>
      </nav>
      <h1 class="text-lg font-bold text-slate-900">Formula List</h1>
    </div>

     <!-- Add Formula -->
    <a href="<?php echo htmlspecialchars(publicUrl('products/formulas.php')); ?>" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
      <span>New Formula</span>
    </a>
  </header>

  <div class="p-6 sm:p-8 max-w-7xl space-y-6">
    <form method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
        <?php foreach (['all' => 'All Formulas', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label): ?>
          <a href="<?php echo htmlspecialchars(feedListUrl(1, $search, $value)); ?>" class="px-3 py-1.5 rounded-lg <?php echo $status === $value ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
      </div>

      <div class="relative flex-1 max-w-sm">
        <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search code, name, description..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-1.5 text-xs">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
        <button type="submit" class=" flex items-center">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </button>
        <?php if (!empty($search)): ?>
          <a href="<?php echo htmlspecialchars(publicUrl('products/feedlist.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2 pr-3 text-slate-400 hover:text-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
          </a>
        <?php endif; ?>
      </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold uppercase border-b border-slate-200">
              <th class="py-3.5 px-6">Feed Code</th>
              <th class="py-3.5 px-6">Formula Name</th>
              <th class="py-3.5 px-6">Creator</th>
              <th class="py-3.5 px-6">Setup Date</th>
              <th class="py-3.5 px-6">Description</th>
              <th class="py-3.5 px-6">Ingredients</th>
              <th class="py-3.5 px-6 text-center">Status</th>
              <th class="py-3.5 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (!$formulas): ?>
              <tr>
                <td colspan="6" class="py-8 text-center text-slate-500">No formula records match the selected filters.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($formulas as $formula): $active = (int) $formula['formula_active_flag'] === 1; ?>
              <tr class="hover:bg-slate-50/50">
                <td class="py-3.5 px-6 font-mono font-bold text-red-600"><?php echo htmlspecialchars($formula['formula_id']); ?></td>
                <td class="py-3.5 px-6 font-semibold"><?php echo htmlspecialchars($formula['formula_name'] ?? ''); ?></td>
                <td class="py-3.5 px-6"><?php echo htmlspecialchars($formula['creator'] ?? ''); ?></td>
                <td class="py-3.5 px-6"><?php echo htmlspecialchars($formula['setup_date'] ?? ''); ?></td>
                <td class="py-3.5 px-6"><?php echo htmlspecialchars($formula['description'] ?? ''); ?></td>
                <td class="py-3.5 px-6"><?php echo count($formula['ingredients']); ?></td>
                <td class="py-3.5 px-6 text-center">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'; ?>"><?php echo $active ? 'Active' : 'Inactive'; ?></span>
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button type="button" onclick="openFormulaModal(<?php echo htmlspecialchars(json_encode($formula, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:text-red-600 font-bold text-xs transition">Edit</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Table Footer Pagination -->
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
        <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $start; ?>-<?php echo $end; ?></span> of <span class="font-bold text-slate-800"><?php echo $total; ?></span> formula records</span>

        <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl(1, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="First Sheet" aria-label="First page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed " title="First Page" aria-label="First page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          <?php if ($page > 1): ?></a><?php else: ?></button><?php endif; ?>

          <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($page - 1, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Sheet" aria-label="Previous page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Previous Page" aria-label="Previous page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          <?php if ($page > 1): ?></a><?php else: ?></button><?php endif; ?>

          <span class="px-3 font-semibold text-slate-700">Records <?php echo $start; ?>-<?php echo $end; ?> of <?php echo $total; ?></span>

          <?php if ($page < $pages): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($page + 1, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Next Page" aria-label="Next page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Next Page" aria-label="Next page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          <?php if ($page < $pages): ?></a><?php else: ?></button><?php endif; ?>

          <?php if ($page < $pages): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($pages, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Last Page" aria-label="Last page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Last Page" aria-label="Last page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          <?php if ($page < $pages): ?></a><?php else: ?></button><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div id="formula-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-6xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden max-h-[92vh] flex flex-col">
      <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-slate-900">Edit Formula</h2>
          <p class="text-[10px] text-slate-500">Update formula metadata and ingredient quantities.</p>
        </div>
        <button type="button" onclick="closeFormulaModal()" aria-label="Close" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <form action="formula_update.php" method="POST" class="flex-1 min-h-0 p-6 sm:p-8 space-y-6 text-xs overflow-y-auto lg:overflow-hidden flex flex-col">
        <input type="hidden" name="formula_id" id="edit-formula-id">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:flex-1 lg:min-h-0">
          <div id="edit-formula-details-card" class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Formula Details</h3>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Editing</span>
            </div>
          
            <!-- Feed Code -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Feed Code *</label>
              <input id="edit-formula-code" disabled class="w-full bg-slate-100 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-slate-900">
            </div>

            <!-- Sold As -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Sold As *</label>
              <input name="formula_name" id="edit-formula-name" required placeholder="Formula name" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>

            <!-- Creator -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Creator</label>
              <input name="creator" id="edit-formula-creator" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>

            <!-- Description -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Description</label>
              <textarea name="description" id="edit-formula-description" rows="2" placeholder="Formula description" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600"></textarea>
            </div>
            
            <!-- Effective Date -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Effective Date</label>
              <input type="date" name="setup_date" id="edit-formula-date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
            
            <!-- Status -->
            <div class="pt-2 border-t border-slate-100 space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Formula Status</label>
              <select name="active_flag" id="edit-formula-status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div id="edit-formula-ingredients-card" class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4 flex flex-col min-h-0 max-h-[45vh] lg:max-h-none">
            <div class="sticky top-0 z-20 bg-white flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Formula Ingredients</h3>
                <p class="text-[11px] text-slate-500">Adjust materials and target quantities per batch.</p>
              </div>
              <button type="button" onclick="addIngredientRow()" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-xl border border-red-200 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add Item</span>
              </button>
            </div>
            
            <div class="flex-1 min-h-0 overflow-y-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="sticky top-0 z-10 bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                  <th class="py-2.5 px-3">Raw Material</th>
                  <th class="py-2.5 px-3 text-right w-36">Quantity (Kg)</th>
                  <th class="py-2.5 px-3 text-center w-12"></th>
                </tr>
              </thead>
              <tbody id="edit-ingredients-list" class="divide-y divide-slate-100"></tbody>
            </table>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex items-center justify-between text-xs font-bold text-slate-800">
              <span>Total Batch Weight:</span>
              <span id="edit-total-weight" class="font-mono text-base text-red-600">0.00 Kg</span>
            </div>
          </div>
        </div>
        
        <div class="shrink-0 flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
          <button type="button" onclick="closeFormulaModal()" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition">Cancel</button>
          <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>Update Formula</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
const ingredientOptions = <?php echo json_encode($ingredientOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
const formulaModal = document.getElementById('formula-modal');
const editFormulaDetailsCard = document.getElementById('edit-formula-details-card');
const editFormulaIngredientsCard = document.getElementById('edit-formula-ingredients-card');
const editFormulaDesktopLayout = window.matchMedia('(min-width: 1024px)');
function syncEditIngredientsCardHeight() { if (editFormulaDesktopLayout.matches) { editFormulaIngredientsCard.style.maxHeight = `${editFormulaDetailsCard.getBoundingClientRect().height}px`; } else { editFormulaIngredientsCard.style.removeProperty('max-height'); } }
function openFormulaModal(formula) { document.getElementById('edit-formula-id').value = formula.formula_id; document.getElementById('edit-formula-code').value = formula.formula_id; document.getElementById('edit-formula-name').value = formula.formula_name || ''; document.getElementById('edit-formula-description').value = formula.description || ''; document.getElementById('edit-formula-creator').value = formula.creator || ''; document.getElementById('edit-formula-date').value = formula.setup_date || ''; document.getElementById('edit-formula-status').value = formula.formula_active_flag; document.getElementById('edit-ingredients-list').innerHTML = ''; (formula.ingredients || []).forEach(addIngredientRow); if (!formula.ingredients?.length) addIngredientRow(); updateTotalWeight(); formulaModal.classList.remove('hidden'); syncEditIngredientsCardHeight(); }
function closeFormulaModal() { formulaModal.classList.add('hidden'); }
function addIngredientRow(item = {}) { const row = document.createElement('tr'); const options = ingredientOptions.map(option => `<option value="${option.ingredients_id}" ${String(option.ingredients_id) === String(item.ingredients_id || '') ? 'selected' : ''}>${option.name}</option>`).join(''); row.innerHTML = `<td class="py-2 px-3"><select name="ingredients[]" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 font-medium focus:bg-white focus:ring-1 focus:ring-red-600">${options}</select></td><td class="py-2 px-3"><input type="number" min="0" step="0.01" name="quantities[]" value="${item.quantity_kgs || ''}" required class="ingredient-quantity w-full text-right bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 font-mono text-xs font-semibold focus:bg-white focus:ring-1 focus:ring-red-600"></td><td class="py-2 px-3 text-center"><button type="button" onclick="this.closest('tr').remove();updateTotalWeight()" aria-label="Remove ingredient" class="text-slate-400 hover:text-red-600 transition p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button></td>`; document.getElementById('edit-ingredients-list').appendChild(row); row.querySelector('input').addEventListener('input', updateTotalWeight); }
function updateTotalWeight() { let total = 0; document.querySelectorAll('.ingredient-quantity').forEach(input => total += Number(input.value) || 0); document.getElementById('edit-total-weight').textContent = `${total.toFixed(2)} Kg`; }
new ResizeObserver(syncEditIngredientsCardHeight).observe(editFormulaDetailsCard);
editFormulaDesktopLayout.addEventListener('change', syncEditIngredientsCardHeight);
</script>
</body></html>