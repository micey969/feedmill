<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$ingredients = [];
$stockDates = [];

$dateResult = $conn->query('SELECT date_time FROM v_ingredients_closing_stock GROUP BY date_time ORDER BY date_time DESC');
if ($dateResult) {
  while ($row = $dateResult->fetch_assoc()) {
    $stockDates[] = $row['date_time'];
  }
}

$totalPages = 1 + count($stockDates);
$currentPage = min($currentPage, $totalPages);
$selectedDate = null;
$isNewEntry = $currentPage === 1;

if ($isNewEntry) {
  $ingredientResult = $conn->query('SELECT name AS ingredient FROM ingredients WHERE active_flag = 1 ORDER BY name ASC');
  if ($ingredientResult) {
    while ($row = $ingredientResult->fetch_assoc()) {
      $ingredients[] = ['name' => $row['ingredient'], 'quantity' => ''];
    }
  }
}

if ($currentPage > 1) {
  $selectedDate = $stockDates[$currentPage - 2] ?? null;
  if ($selectedDate !== null) {
    $stockStmt = $conn->prepare('SELECT ingredients AS ingredient, quantity_kgs FROM v_ingredients_closing_stock WHERE date_time = ? ORDER BY ingredients ASC');
    if (!$stockStmt) {
      http_response_code(500);
      exit('Unable to load the saved stock record: ' . $conn->error);
    }
    $stockStmt->bind_param('s', $selectedDate);
    $stockStmt->execute();
    $savedQuantities = [];
    foreach ($stockStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
      $ingredients[] = ['name' => $row['ingredient'], 'quantity' => $row['quantity_kgs']];
      $savedQuantities[$row['ingredient']] = $row['quantity_kgs'];
    }
    $stockStmt->close();
    foreach ($ingredients as &$ingredient) {
      $ingredient['quantity'] = $savedQuantities[$ingredient['name']] ?? '';
    }
    unset($ingredient);
  }
}

$ingredientColumns = array_chunk($ingredients, max(1, (int) ceil(count($ingredients) / 3)));
$ingredientColumns = array_pad($ingredientColumns, 3, []);
$formDate = $isNewEntry ? date('Y-m-d') : date('Y-m-d', strtotime($selectedDate));
$pageUrl = publicUrl('products/physical.php');
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Actual Daily Physical Stock';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

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
          <span class="text-slate-600">Product Management</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Physical Stock</h1>
      </div>

      <!-- Navigation & Action Buttons -->
      <div class="flex items-center gap-3">
        <!-- Record Navigation Toolbar -->
        <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <a href="<?php echo htmlspecialchars($pageUrl); ?>" class="px-2 py-1 <?php echo $currentPage === 1 ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="New Entry">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          </a>
          <a href="<?php echo htmlspecialchars($pageUrl . '?page=' . max(1, $currentPage - 1)); ?>" class="px-2 py-1 <?php echo $currentPage === 1 ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Previous Record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </a>
          <span class="px-3 font-semibold text-slate-700"><?php echo $isNewEntry ? 'New Stock Entry' : htmlspecialchars(date('M j, Y', strtotime($selectedDate))); ?></span>
          <a href="<?php echo htmlspecialchars($pageUrl . '?page=' . min($totalPages, $currentPage + 1)); ?>" class="px-2 py-1 <?php echo $currentPage === $totalPages ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Next Record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </a>
          <a href="<?php echo htmlspecialchars($pageUrl . '?page=' . $totalPages); ?>" class="px-2 py-1 <?php echo $currentPage === $totalPages ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:text-slate-900 hover:bg-white'; ?> rounded-lg transition" title="Last Record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          </a>
        </div>
      </div>
    </header>

    <!-- Printable Content Container -->
    <div id="printable-content" class="p-6 sm:p-8 max-w-7xl space-y-6">

      <!-- Sheet Top Header -->
      <div id="sheet-header" class="text-center border-b border-slate-200 pb-4 hidden">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">East Caribbean Feeds Limited</h2>
        <h3 class="text-xl font-bold text-red-600 uppercase tracking-wide mt-0.5">Physical Stock</h3>
      </div>
      
      <form action="physical_save.php" method="POST" class="space-y-6">
        <input type="hidden" name="return_page" value="1">

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-6">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Stock Take Date</label>
              <input type="date" name="stock_date" value="<?php echo htmlspecialchars($formDate); ?>" <?php echo $isNewEntry ? '' : 'readonly'; ?> required class="bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span class="no-print inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?php echo $isNewEntry ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200'; ?> border">
              <span class="w-2 h-2 rounded-full <?php echo $isNewEntry ? 'bg-blue-500' : 'bg-slate-500'; ?>"></span>
              <?php echo $isNewEntry ? 'New stock entry' : 'Saved stock record'; ?>
            </span>
            <span class="text-xs text-slate-500 font-medium"><?php echo count($ingredients); ?> <?php echo $isNewEntry ? 'active ingredients' : 'recorded ingredients'; ?></span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 print-columns">
          <?php foreach ($ingredientColumns as $columnIndex => $columnIngredients): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden print-column">
              <div class="p-4 space-y-3 text-xs print-column-body">
                <?php foreach ($columnIngredients as $ingredient): ?>
                  <div class="flex items-center justify-between gap-3 print-row">
                    <label for="ingredient-<?php echo (int) $columnIndex . '-' . htmlspecialchars((string) crc32($ingredient['name'])); ?>" class="font-bold text-slate-800 truncate"><?php echo htmlspecialchars($ingredient['name']); ?></label>
                    <input id="ingredient-<?php echo (int) $columnIndex . '-' . htmlspecialchars((string) crc32($ingredient['name'])); ?>" type="number" name="quantities[<?php echo htmlspecialchars($ingredient['name']); ?>]" min="0" step="1" value="<?php echo htmlspecialchars((string) $ingredient['quantity']); ?>" <?php echo $isNewEntry ? '' : 'readonly'; ?> class="w-32 text-right bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 font-mono font-semibold text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-600">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <?php if ($isNewEntry): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center justify-between no-print">
          <p class="text-xs text-slate-500 font-medium">Verify physical counts prior to saving stock records.</p>
            <div class="flex items-center gap-3">
              <button type="reset" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition">Reset Counts</button>
              <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Save Physical Stock Count</span>
              </button>
            </div>
          </div>
         <?php endif; ?>
      </form>
    </div>
  </main>

</body>
</html>
