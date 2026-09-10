<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['search'] ?? ''));
$status = $_GET['status'] ?? 'all';
$status = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';
$view = 'formula_metadata_composition_view';
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
  
  <div class="h-1.5 bg-red-600 w-full"></div>

  <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex items-center justify-between sticky top-0 z-10">
    <div>
      <nav class="flex gap-2 text-xs text-slate-400"><a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600">Main Hub</a><span>/</span><span>Product Management</span></nav><h1 class="text-lg font-bold text-slate-900">Formula List</h1></div>
    <a href="<?php echo htmlspecialchars(publicUrl('products/formulas.php')); ?>" class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-xl">New Formula</a>
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
                  <button type="button" onclick="openFormulaModal(<?php echo htmlspecialchars(json_encode($formula, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" class="text-slate-600 hover:text-red-600 font-semibold">Edit</button>
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
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed " title="First Sheet" aria-label="First page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          <?php if ($page > 1): ?></a><?php else: ?></button><?php endif; ?>

          <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($page - 1, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Sheet" aria-label="Previous page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Previous Sheet" aria-label="Previous page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          <?php if ($page > 1): ?></a><?php else: ?></button><?php endif; ?>

          <span class="px-3 font-semibold text-slate-700">Records <?php echo $start; ?>-<?php echo $end; ?> of <?php echo $total; ?></span>

          <?php if ($page < $pages): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($page + 1, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Next Sheet" aria-label="Next page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Next Sheet" aria-label="Next page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          <?php if ($page < $pages): ?></a><?php else: ?></button><?php endif; ?>

          <?php if ($page < $pages): ?>
            <a href="<?php echo htmlspecialchars(feedListUrl($pages, $search, $status)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Last Sheet" aria-label="Last page">
          <?php else: ?>
            <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Last Sheet" aria-label="Last page">
          <?php endif; ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          <?php if ($page < $pages): ?></a><?php else: ?></button><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div id="formula-modal" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] overflow-y-auto">
      <div class="p-5 bg-slate-50 border-b flex justify-between">
        <div>
          <h2 class="font-bold">Edit Formula</h2>
          <p class="text-[10px] text-slate-500">Update formula metadata and ingredient quantities.</p>
        </div>
        <button type="button" onclick="closeFormulaModal()" aria-label="Close">&times;</button>
      </div>
      
      <form action="feedlist_save.php" method="POST" class="p-6 space-y-6 text-xs">
        
        <input type="hidden" name="formula_id" id="edit-formula-id">
        
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
          <div class="lg:col-span-2 space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase">Formula Details</h3>
          
            <!-- Feed Code -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Feed Code *</label>
              <input id="edit-formula-code" disabled class="w-full bg-slate-100 border rounded-lg px-3 py-2 font-mono">
            </div>

            <!-- Sold As -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Sold As *</label>
              <input name="formula_name" id="edit-formula-name" required placeholder="Formula name" class="w-full bg-slate-50 border rounded-lg px-3 py-2">
            </div>

            <!-- Creator -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Creator</label>
              <input name="creator" id="edit-formula-creator" class="w-full bg-slate-50 border rounded-lg px-3 py-2">
            </div>

            <!-- Description -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Description</label>
              <textarea name="description" id="edit-formula-description" rows="4" placeholder="Description" class="w-full bg-slate-50 border rounded-lg px-3 py-2"></textarea>
            </div>
            
            <!-- Effective Date -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Effective Date</label>
              <input type="date" name="setup_date" id="edit-formula-date" required class="w-full bg-slate-50 border rounded-lg px-3 py-2">
            </div>
            
            <!-- Status -->
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Status</label>
              <select name="active_flag" id="edit-formula-status" class="w-full bg-slate-50 border rounded-lg px-3 py-2">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <div class="lg:col-span-3 space-y-4">
            <div class="flex justify-between">
              <h3 class="text-[10px] font-bold text-slate-400 uppercase">Formula Ingredients</h3>
              <button type="button" onclick="addIngredientRow()" class="px-3 py-1.5 bg-red-50 text-red-700 font-bold rounded-xl border">Add Item</button>
            </div>
            
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="bg-slate-50 font-bold uppercase">
                  <th class="p-2">Raw Material</th>
                  <th class="p-2">Quantity (Kg)</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="edit-ingredients-list"></tbody>
            </table>
            
            <div class="bg-slate-50 rounded-xl p-4 border flex justify-between font-bold">Total Batch Weight:<span id="edit-total-weight" class="font-mono text-red-600">0.00 Kg</span></div>
          </div>
        </div>
        
        <div class="border-t pt-4 flex justify-end gap-3">
          <button type="button" onclick="closeFormulaModal()" class="px-4 py-2 bg-slate-100 rounded-xl">Cancel</button>
          <button type="submit" class="px-5 py-2 bg-red-600 text-white font-bold rounded-xl">Update Formula</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script>
const ingredientOptions = <?php echo json_encode($ingredientOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
const formulaModal = document.getElementById('formula-modal');
function openFormulaModal(formula) { document.getElementById('edit-formula-id').value = formula.formula_id; document.getElementById('edit-formula-code').value = formula.formula_id; document.getElementById('edit-formula-name').value = formula.formula_name || ''; document.getElementById('edit-formula-description').value = formula.description || ''; document.getElementById('edit-formula-date').value = formula.setup_date || ''; document.getElementById('edit-formula-status').value = formula.formula_active_flag; document.getElementById('edit-ingredients-list').innerHTML = ''; (formula.ingredients || []).forEach(addIngredientRow); if (!formula.ingredients?.length) addIngredientRow(); updateTotalWeight(); formulaModal.classList.remove('hidden'); }
function closeFormulaModal() { formulaModal.classList.add('hidden'); }
function addIngredientRow(item = {}) { const row = document.createElement('tr'); const options = ingredientOptions.map(option => `<option value="${option.ingredients_id}" ${String(option.ingredients_id) === String(item.ingredients_id || '') ? 'selected' : ''}>${option.name}</option>`).join(''); row.innerHTML = `<td class="p-2"><select name="ingredients[]" required class="w-full border rounded-lg px-2 py-1.5">${options}</select></td><td class="p-2"><input type="number" min="0" step="0.01" name="quantities[]" value="${item.quantity_kgs || ''}" required class="ingredient-quantity w-full border rounded-lg px-2 py-1.5"></td><td class="p-2"><button type="button" onclick="this.closest('tr').remove();updateTotalWeight()">&times;</button></td>`; document.getElementById('edit-ingredients-list').appendChild(row); row.querySelector('input').addEventListener('input', updateTotalWeight); }
function updateTotalWeight() { let total = 0; document.querySelectorAll('.ingredient-quantity').forEach(input => total += Number(input.value) || 0); document.getElementById('edit-total-weight').textContent = `${total.toFixed(2)} Kg`; }
</script>
</body></html>