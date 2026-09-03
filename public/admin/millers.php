<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

// Pagination settings
$recordsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Get search term from GET parameter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// First, get total count
if (!empty($searchTerm)) {
  $countQuery = "SELECT COUNT(*) as total FROM millers WHERE (full_name LIKE ? OR job_title LIKE ?)";
  $stmt = $conn->prepare($countQuery);
  
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  
  $searchParam = '%' . $searchTerm . '%';
  $stmt->bind_param('ss', $searchParam, $searchParam);
  $stmt->execute();
  $countResult = $stmt->get_result();
  $countRow = $countResult->fetch_assoc();
  $totalRecords = $countRow['total'];
} else {
  // Get count of all millers if no search term
  $countQuery = "SELECT COUNT(*) as total FROM millers";
  $countResult = $conn->query($countQuery);
  $countRow = $countResult->fetch_assoc();
  $totalRecords = $countRow['total'];
}

// Now fetch paginated results
if (!empty($searchTerm)) {
  $query = "SELECT * FROM millers WHERE (full_name LIKE ? OR job_title LIKE ?) ORDER BY active_flag DESC, full_name ASC LIMIT ? OFFSET ?";
  $stmt = $conn->prepare($query);
  
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  
  $searchParam = '%' . $searchTerm . '%';
  $stmt->bind_param('ssii', $searchParam, $searchParam, $recordsPerPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  // Fetch paginated millers if no search term
  $query = "SELECT * FROM millers ORDER BY active_flag DESC, full_name ASC LIMIT ? OFFSET ?";
  $stmt = $conn->prepare($query);
  
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);
  }
  
  $stmt->bind_param('ii', $recordsPerPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
}

if (!$result) {
  die("Query failed: " . $conn->error);
}

// Get millers data for current page
$millers = $result->fetch_all(MYSQLI_ASSOC);

// Calculate pagination info
$totalPages = ceil($totalRecords / $recordsPerPage);
$displayStart = $totalRecords > 0 ? ($offset + 1) : 0;
$displayEnd = min($offset + $recordsPerPage, $totalRecords);

$jobColors = [
  'Mill Operator' => 'bg-slate-100 text-slate-500 border-slate-200',
  'Assistant Senior Miller' => 'bg-blue-50 text-blue-700 border-blue-200',
  'Shift Supervisor' => 'bg-red-50 text-red-700 border-red-200',
  'Assistant Mill Supervisor' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
];
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Millers Administration';
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
          <span class="text-slate-600">Administration</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Millers Roster</h1>
      </div>

      <!-- Quick Search Bar -->
      <form method="GET" class="relative w-64">
        <input type="text" name="search" placeholder="Search miller or position..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 <?php echo !empty($searchTerm) ? 'pr-9' : 'pr-3'; ?> py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
        <button type="submit" class=" flex items-center">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </button>
        <?php if (!empty($searchTerm)): ?>
          <a href="<?php echo htmlspecialchars(publicUrl('admin/millers.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2.5 pr-3 text-slate-400 hover:text-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
          </a>
        <?php endif; ?>
      </form>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      
      <!-- ADD NEW MILLER FORM CARD -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4 flex items-center gap-2">
          <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
          Add New Miller Record
        </h2>

        <form action="millers_save.php" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
          <!-- Full Name -->
          <div class="sm:col-span-5 space-y-1">
            <label class="text-xs font-semibold text-slate-600">Full Name</label>
            <input type="text" name="full_name" placeholder="e.g. Brionne Campbell" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Position -->
          <div class="sm:col-span-5 space-y-1">
            <label class="text-xs font-semibold text-slate-600">Job Position</label>
            <select name="job_title" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              <option selected>Select Position</option>
              <option value="Mill Operator">Mill Operator</option>
              <option value="Assistant Senior Miller">Assistant Senior Miller</option>
              <option value="Shift Supervisor">Shift Supervisor</option>
              <option value="Assistant Mill Supervisor">Assistant Mill Supervisor</option>
            </select>
          </div>

          <!-- Submit Button -->
          <div class="sm:col-span-2">
            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
              <span>Add Miller</span>
            </button>
          </div>
        </form>
      </div>

      <!-- MILLERS LIST DATA TABLE -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-slate-500">Click on any record row to edit staffing details.</p>
          <span class="text-xs font-mono font-bold text-slate-600"><?php echo $totalRecords; ?> Registered Personnel</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                  <th class="py-3.5 px-6">Full Name</th>
                  <th class="py-3.5 px-6">Job Position</th>
                  <th class="py-3.5 px-6 text-center">Active Status</th>
                  <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                
                <?php if (empty($millers)): ?>
                  <tr>
                    <td colspan="4" class="py-6 px-6 text-center text-slate-500">
                      <?php echo !empty($searchTerm) ? 'No millers found matching your search.' : 'No millers registered yet.'; ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($millers as $miller): ?>
                    <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                      <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo htmlspecialchars($miller['full_name']); ?></td>
                      <td class="py-3.5 px-6">
                        <?php $badgeClass = $jobColors[$miller['job_title']] ?? 'bg-gray-50 text-gray-700 border-gray-200';?>

                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?php echo $badgeClass; ?>">
                          <?php echo htmlspecialchars($miller['job_title']); ?>
                        </span>
                      </td>
                      <td class="py-3.5 px-6 text-center"><input type="checkbox" disabled class="w-4 h-4 text-red-600 rounded border-slate-300 cursor-pointer" <?php echo (int) $miller ['active_flag'] === 1 ? 'checked' : ''; ?>></td>
                      <td class="py-3.5 px-6 text-right">
                        <button type="button" onclick="openMillerModal(<?php echo (int) $miller['user_id']; ?>, <?php echo htmlspecialchars(json_encode($miller['full_name']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($miller['job_title']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo (int) $miller['active_flag']; ?>)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                          Edit
                        </button>                      
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>

              </tbody>
            </table>
          </div>

          <!-- Table Pagination Footer -->
          <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $displayStart; ?>-<?php echo $displayEnd; ?></span> of <span class="font-bold text-slate-800"><?php echo $totalRecords; ?></span> Millers</span>

            <div class="flex items-center gap-1">
              <!-- Previous Button -->
              <?php if ($currentPage > 1): ?>
                <a href="<?php echo htmlspecialchars(publicUrl('admin/millers.php') . '?page=' . ($currentPage - 1) . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
              <?php else: ?>
                <button disabled class="p-2 rounded-lg bg-white border border-slate-200 text-slate-300 cursor-not-allowed">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
              <?php endif; ?>

              <!-- Page Numbers -->
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $currentPage): ?>
                  <button class="px-3 py-1 rounded-lg bg-red-600 text-white font-bold text-xs shadow-xs"><?php echo $i; ?></button>
                <?php else: ?>
                  <a href="<?php echo htmlspecialchars(publicUrl('admin/millers.php') . '?page=' . $i . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '')); ?>" class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition font-bold text-xs"><?php echo $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>

              <!-- Next Button -->
              <?php if ($currentPage < $totalPages): ?>
                <a href="<?php echo htmlspecialchars(publicUrl('admin/millers.php') . '?page=' . ($currentPage + 1) . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition">
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
    </div>

    <div id="edit-miller-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Edit Miller Details</h2>
            <p class="text-[10px] text-slate-500">Update the miller's information and status.</p>
          </div>
          <button onclick="document.getElementById('edit-miller-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <form action="millers_update.php" method="POST" class="p-6 overflow-y-auto space-y-6 text-xs">
          <input type="hidden" name="user_id" id="edit-miller-user-id">
          
          <div class="space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Account Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
                <input type="text" name="full_name" id="edit-miller-full-name" required placeholder="e.g. Jane Doe" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Account Status</label>
                <select name="status" id="edit-miller-status" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Job Title</label>
                <select name="job_title" id="edit-miller-job-title" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
                  <option value="Mill Operator">Mill Operator</option>
                  <option value="Assistant Senior Miller">Assistant Senior Miller</option>
                  <option value="Shift Supervisor">Shift Supervisor</option>
                  <option value="Assistant Mill Supervisor">Assistant Mill Supervisor</option>
                </select>
              </div>
            </div>
          </div>

          <hr class="border-slate-100">

          <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
            <button type="button" onclick="document.getElementById('edit-miller-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition">
              Update Miller Info
            </button>
          </div>

        </form>

      </div>
    </div>
  </main>
</body>
</html>