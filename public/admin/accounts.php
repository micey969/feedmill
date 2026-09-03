<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$recordsPerPage = 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$searchTerm = trim($_GET['search'] ?? '');
$searchParam = '%' . $searchTerm . '%';

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM accounts WHERE full_name LIKE ? OR username LIKE ? OR job_title LIKE ?');
$countStmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
$countStmt->execute();
$totalRecords = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRecords / $recordsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recordsPerPage;

$accountsStmt = $conn->prepare('SELECT user_id, full_name, username, password, image_name, admin_flag, active_flag, job_title FROM accounts WHERE full_name LIKE ? OR username LIKE ? OR job_title LIKE ? ORDER BY active_flag DESC, full_name ASC, user_id ASC LIMIT ? OFFSET ?');
$accountsStmt->bind_param('sssii', $searchParam, $searchParam, $searchParam, $recordsPerPage, $offset);
$accountsStmt->execute();
$accounts = $accountsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$accountsStmt->close();

$displayStart = $totalRecords > 0 ? $offset + 1 : 0;
$displayEnd = min($offset + $recordsPerPage, $totalRecords);
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - User Accounts Administration';
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
          <span class="text-slate-600">Administration</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">User Accounts</h1>
      </div>

      <!-- Quick Search & Create User Trigger -->
      <div class="flex items-center gap-3">
        <form method="GET" class="relative w-64">
          <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search user or username..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 <?php echo $searchTerm !== '' ? 'pr-9' : 'pr-3'; ?> py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          <button type="submit" aria-label="Search" class="absolute left-3 top-2.5"><svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></button>
          <?php if ($searchTerm !== ''): ?>
            <a href="<?php echo htmlspecialchars(publicUrl('admin/accounts.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2.5 pr-3 text-slate-400 hover:text-slate-700 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
            </a>          
          <?php endif; ?>
        </form>

        <button onclick="document.getElementById('add-user-modal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
          <span>Add New User</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-6xl space-y-4">
      
      <!-- Helper Banner -->
      <div class="flex items-center justify-between bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl text-xs font-medium">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span>Click on any record row to modify account details, permissions, or access credentials.</span>
        </div>
        <span class="font-bold text-blue-600 font-mono"><?php echo $totalRecords; ?> Total Users</span>
      </div>

      <!-- Users Data Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Profile Image</th>
                <th class="py-3.5 px-6">Full Name</th>
                <th class="py-3.5 px-6">Job Title</th>
                <th class="py-3.5 px-6">Username</th>
                <th class="py-3.5 px-6">Password</th>
                <th class="py-3.5 px-6 text-center">Admin Rights</th>
                <th class="py-3.5 px-6 text-center">Active Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$accounts): ?>
                <tr><td colspan="8" class="py-6 px-6 text-center text-slate-500"><?php echo $searchTerm !== '' ? 'No accounts found matching your search.' : 'No system accounts found.'; ?></td></tr>
              <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                  <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                    <td class="py-3.5 px-6"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-600 text-xs uppercase"><img src="<?php echo htmlspecialchars(publicUrl('images/' . $account['image_name']) ?: 'avatar.png'); ?>" alt="User Avatar" class="w-full h-full object-cover rounded-full"></div></td>
                    <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo htmlspecialchars($account['full_name']); ?></td>
                    <td class="py-3.5 px-6 font-semibold text-slate-900"><?php echo htmlspecialchars($account['job_title']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-600"><?php echo htmlspecialchars($account['username']); ?></td>
                    <td class="py-3.5 px-6 font-mono text-slate-400">&bull;&bull;&bull;&bull;&bull;&bull;</td>
                    <td class="py-3.5 px-6 text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo (int) $account['admin_flag'] === 1 ? 'bg-red-100 text-red-700 border-red-200' : 'bg-blue-100 text-blue-600 border-blue-200'; ?> border"><?php echo (int) $account['admin_flag'] === 1 ? 'Admin' : 'User'; ?></span></td>
                    <td class="py-3.5 px-6 text-center"><input type="checkbox" disabled class="w-4 h-4 text-red-600 rounded border-slate-300 cursor-pointer" <?php echo (int) $account['active_flag'] === 1 ? 'checked' : ''; ?>></td>
                    <td class="py-3.5 px-6 text-right"><button type="button" onclick="openAccountModal(<?php echo htmlspecialchars(json_encode($account), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $displayStart; ?>-<?php echo $displayEnd; ?></span> of <span class="font-bold text-slate-800"><?php echo $totalRecords; ?></span> system accounts</span>

          <div class="flex items-center gap-1">
            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars(publicUrl('admin/accounts.php'). '?page=' . ($currentPage - 1) . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
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
                <a href="<?php echo htmlspecialchars(publicUrl('admin/accounts.php'). '?page=' . $page . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-bold text-xs"><?php echo $page; ?></a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars(publicUrl('admin/accounts.php'). '?page=' . ($currentPage + 1) . ($searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '')); ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
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


    <div id="add-user-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Add New User Account</h2>
            <p class="text-[10px] text-slate-500">Create login credentials and set system access levels.</p>
          </div>
          <button onclick="document.getElementById('add-user-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <form action="accounts_save.php" method="POST" class="p-6 overflow-y-auto space-y-6 text-xs">
          
          <div class="space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Account Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Jane Doe" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Username *</label>
                <input type="text" name="username" required placeholder="e.g. jdoe" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Job Title *</label>
                <input type="text" name="job_title" required placeholder="e.g. Production Supervisor" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Image *</label>
                <input type="text" name="image_name" required placeholder="e.g. BS02542.png" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
            </div>
          </div>

          <hr class="border-slate-100">

          <div class="space-y-4">
            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Security & Permissions</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-bold text-slate-700 mb-1">Access Role *</label>
                <select name="role" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
                  <option selected>Select Role</option>
                  <option value="1">Administrator</option>
                  <option value="0">User</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-slate-700 mb-1">Account Status</label>
                <select name="status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Password *</label>
                <input type="password" name="password" required placeholder="••••••••••••" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
            <button type="button" onclick="document.getElementById('add-user-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-600/20 transition">
              Create User Account
            </button>
          </div>

        </form>

      </div>
    </div>

    <div id="edit-user-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div><h2 class="text-base font-bold text-slate-900">Edit User Account</h2><p class="text-[10px] text-slate-500">Update account details, permissions, or credentials.</p></div>
          <button type="button" onclick="document.getElementById('edit-user-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg" aria-label="Close">&times;</button>
        </div>
        <form action="accounts_update.php" method="POST" class="p-6 overflow-y-auto space-y-6 text-xs">
          <input type="hidden" name="user_id" id="edit-user-id">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-bold text-slate-700 mb-1">Full Name *</label><input type="text" name="full_name" id="edit-user-full-name" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Username *</label><input type="text" name="username" id="edit-user-username" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Job Title</label><input type="text" name="job_title" id="edit-user-job-title" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
            <div><label class="block font-bold text-slate-700 mb-1">Account Status</label><select name="active_flag" id="edit-user-active" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div><label class="block font-bold text-slate-700 mb-1">Access</label><select name="admin_flag" id="edit-user-admin" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"><option value="0">User</option><option value="1">Administrator</option></select></div>
            <div><label class="block font-bold text-slate-700 mb-1">New Password</label><input type="password" name="password" placeholder="Leave blank to keep current" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 font-medium"></div>
          </div>
          <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3"><button type="button" onclick="document.getElementById('edit-user-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</button><button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl">Update User Account</button></div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>