<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Millers Administration';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<body class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto">
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
      <div class="relative w-64">
        <input type="text" placeholder="Search miller or position..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </div>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      
      <!-- ADD NEW MILLER FORM CARD -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4 flex items-center gap-2">
          <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
          Add New Miller Record
        </h2>

        <form action="millers.php" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
          <!-- Full Name -->
          <div class="sm:col-span-5 space-y-1">
            <label class="text-xs font-semibold text-slate-600">Full Name</label>
            <input type="text" name="full_name" placeholder="e.g. Brionne Campbell" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Position -->
          <div class="sm:col-span-5 space-y-1">
            <label class="text-xs font-semibold text-slate-600">Job Position</label>
            <input type="text" name="position" placeholder="e.g. Senior Miller" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          </div>

          <!-- Submit Button -->
          <div class="sm:col-span-2">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              <span>Add</span>
            </button>
          </div>
        </form>
      </div>

      <!-- MILLERS LIST DATA TABLE -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <p class="text-xs font-medium text-slate-500">Click on any record row to edit staffing details.</p>
          <span class="text-xs font-mono font-bold text-slate-600">5 Registered Personnel</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                  <th class="py-3.5 px-6">Full Name</th>
                  <th class="py-3.5 px-6">Job Position</th>
                  <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                
                <!-- Row 1 -->
                <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                  <td class="py-3.5 px-6 font-semibold text-slate-900">Brionne Campbell</td>
                  <td class="py-3.5 px-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                      Senior Miller
                    </span>
                  </td>
                  <td class="py-3.5 px-6 text-right">
                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                  </td>
                </tr>

                <!-- Row 2 -->
                <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                  <td class="py-3.5 px-6 font-semibold text-slate-900">Brionne Campbell</td>
                  <td class="py-3.5 px-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      Shift Supervisor
                    </span>
                  </td>
                  <td class="py-3.5 px-6 text-right">
                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                  </td>
                </tr>

                <!-- Row 3 -->
                <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                  <td class="py-3.5 px-6 font-semibold text-slate-900">Brionne Campbell</td>
                  <td class="py-3.5 px-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                      Assiistant Senior Miller
                    </span>
                  </td>
                  <td class="py-3.5 px-6 text-right">
                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                  </td>
                </tr>

                <!-- Row 4 -->
                <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                  <td class="py-3.5 px-6 font-semibold text-slate-900">Brionne Campbell</td>
                  <td class="py-3.5 px-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                      MIIl Operator
                    </span>
                  </td>
                  <td class="py-3.5 px-6 text-right">
                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                  </td>
                </tr>

                <!-- Row 5 -->
                <tr class="hover:bg-slate-50/80 transition cursor-pointer">
                  <td class="py-3.5 px-6 font-semibold text-slate-900">Brionne Campbell</td>
                  <td class="py-3.5 px-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                      Assiistant MIIl Supervisor
                    </span>
                  </td>
                  <td class="py-3.5 px-6 text-right">
                    <button class="text-blue-600 hover:text-blue-800 font-bold text-xs">Edit</button>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>

          <!-- Table Pagination Footer -->
          <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800">1-5</span> of <span class="font-bold text-slate-800">5</span> records</span>

            <div class="flex items-center gap-1">
              <button disabled class="p-2 rounded-lg bg-white border border-slate-200 text-slate-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
              </button>
              <button class="px-3 py-1 rounded-lg bg-red-600 text-white font-bold text-xs shadow-xs">1</button>
              <button disabled class="p-2 rounded-lg bg-white border border-slate-200 text-slate-300 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

</body>
</html>