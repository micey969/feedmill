<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$recordsPerPage = 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$searchTerm = trim($_GET['search'] ?? '');
$searchParam = '%' . $searchTerm . '%';

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM suppliers WHERE contact_person LIKE ? OR company_name LIKE ? OR country LIKE ?');
$countStmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
$countStmt->execute();
$totalRecords = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRecords / $recordsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recordsPerPage;

$suppliersStmt = $conn->prepare('SELECT supplier_id, contact_person, company_name, country, phone, email FROM suppliers WHERE contact_person LIKE ? OR company_name LIKE ? OR country LIKE ? ORDER BY company_name ASC LIMIT ? OFFSET ?');
$suppliersStmt->bind_param('sssii', $searchParam, $searchParam, $searchParam, $recordsPerPage, $offset);
$suppliersStmt->execute();
$suppliers = $suppliersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$suppliersStmt->close();

$displayStart = $totalRecords > 0 ? $offset + 1 : 0;
$displayEnd = min($offset + $recordsPerPage, $totalRecords);
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Suppliers';
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
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Inventory</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Suppliers</h1>
      </div>

      <!-- Quick Search & Create User Trigger -->
      <div class="flex items-center gap-3">
        <form method="GET" class="relative w-64">
          <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search suppliers..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 <?php echo $searchTerm !== '' ? 'pr-9' : 'pr-3'; ?> py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          <button type="submit" aria-label="Search" class="absolute left-3 top-2.5"><svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></button>
          <?php if ($searchTerm !== ''): ?>
            <a href="<?php echo htmlspecialchars(publicUrl('inventory/suppliers.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2.5 pr-3 text-slate-400 hover:text-slate-700 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
            </a>          
          <?php endif; ?>
        </form>

        <button onclick="document.getElementById('add-supplier-modal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
          <span>Add New Supplier</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl space-y-4">
      
      <!-- Helper Banner -->
      <div class="flex items-center justify-between bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl text-xs font-medium">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span>Click on any record row to modify supplier details.</span>
        </div>
        <span class="font-bold text-blue-600 font-mono"><?php echo $totalRecords; ?> Total Suppliers</span>
      </div>

      <!-- Suppliers Data Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Company Name</th>
                <th class="py-3.5 px-6">Contact Person</th>
                <th class="py-3.5 px-6">Country</th>
                <th class="py-3.5 px-6">Phone</th>
                <th class="py-3.5 px-6">Email</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$suppliers): ?>
                <tr><td colspan="8" class="py-6 px-6 text-center text-slate-500"><?php echo $searchTerm !== '' ? 'No suppliers found matching your search.' : 'No suppliers found.'; ?></td></tr>
              <?php else: ?>
                <?php foreach ($suppliers as $supplier): ?>
                  <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                    <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                    <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-600"><?php echo htmlspecialchars($supplier['country']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-400"><?php echo htmlspecialchars($supplier['phone']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-400"><?php echo htmlspecialchars($supplier['email']); ?></td>
                    <td class="py-3.5 px-6 text-right"><button type="button" onclick="openSupplierModal(<?php echo htmlspecialchars(json_encode($supplier), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $displayStart; ?>-<?php echo $displayEnd; ?></span> of <span class="font-bold text-slate-800"><?php echo $totalRecords; ?></span> Suppliers</span>

          <div class="flex items-center gap-1">
            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars(publicUrl('inventory/suppliers.php'). '?page=' . ($currentPage - 1) . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <?php else: ?>
              <button disabled class="p-2 rounded-lg bg-white border border-slate-200 text-slate-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
              </button>
            <?php endif; ?>
            
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
              <?php if ($page === $currentPage): ?>
                <button class="px-3 py-1 rounded-lg bg-red-600 text-white font-bold text-xs shadow-xs"><?php echo $page; ?></button>
              <?php else: ?>
                <a href="<?php echo htmlspecialchars(publicUrl('inventory/suppliers.php'). '?page=' . $page . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-bold text-xs"><?php echo $page; ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars(publicUrl('inventory/suppliers.php'). '?page=' . ($currentPage + 1) . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
              </a>
            <?php else: ?>
              <button disabled class="p-2 rounded-lg bg-white border border-slate-200 text-slate-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
              </button>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>


    <div id="add-supplier-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Add New Supplier</h2>
            <p class="text-[10px] text-slate-500">Fill in supplier details to register them in the feeds database.</p>
          </div>
          <button onclick="document.getElementById('add-supplier-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <form action="suppliers_save.php" method="POST" class="p-6 overflow-y-auto space-y-6 text-xs">
          
          <div class="space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Company Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Company Name *</label>
                <input type="text" name="company_name" required placeholder="e.g. A&C Grains" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Contact Person *</label>
                <input type="text" name="contact_person" required placeholder="e.g. John Doe" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
            </div>
          </div>

          <hr class="border-slate-100">

          <!-- SECTION 3: COMMUNICATION & ONLINE PRESENCE -->
          <div class="space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Contact Channels</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Phone -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Phone Number *</label>
                <input type="tel" name="phone" required placeholder="+1 (246) 000-0000" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <!-- Email -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Email Address *</label>
                <input type="email" name="email" required placeholder="contact@supplier.com" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <!-- Website -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Country *</label>
                <input type="text" name="country" required placeholder="Barbados" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
            <button type="button" onclick="document.getElementById('add-supplier-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition">
              Save Supplier
            </button>
          </div>

        </form>

      </div>
    </div>

    <div id="edit-supplier-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div><h2 class="text-base font-bold text-slate-900">Edit Supplier Details</h2><p class="text-[10px] text-slate-500">Update supplier information and contact details.</p></div>
          <button type="button" onclick="document.getElementById('edit-supplier-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg" aria-label="Close">&times;</button>
        </div>
        <form action="supplier_update.php" method="POST" class="p-6 overflow-y-auto space-y-6 text-xs">
          <input type="hidden" name="supplier_id" id="edit-supplier-id">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold text-slate-700 mb-1">Full Name *</label><input type="text" name="full_name" id="edit-supplier-full-name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Company Name *</label><input type="text" name="company_name" id="edit-supplier-company-name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Email *</label><input type="email" name="email" id="edit-supplier-email" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Phone *</label><input type="tel" name="phone" id="edit-supplier-phone" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">New Password</label><input type="password" name="password" placeholder="Leave blank to keep current" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
          </div>
          <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3"><button type="button" onclick="document.getElementById('edit-supplier-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</button><button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl">Update Supplier Details</button></div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>