<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Formula List';
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
        <h1 class="text-lg font-bold text-slate-900">Formula List</h1>
      </div>

      <!-- Add Formula  -->
      <a href="<?php echo htmlspecialchars(publicUrl('products/formulas.php')); ?>" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>New Formula</span>
      </a>
    </header>

    <!-- Workspace Body -->
    <div class="p-6 sm:p-8 max-w-7xl space-y-6">
      
      <!-- CONTROLS & FILTER BAR CARD -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
          <button type="button" class="px-3 py-1.5 rounded-lg bg-white text-slate-900 shadow-xs">All Formulas</button>
          <button type="button" class="px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-900 transition">Active</button>
          <button type="button" class="px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-900 transition">Inactive</button>
        </div>

        <!-- Search Bar -->
        <div class="relative flex-1 max-w-sm">
          <input type="text" placeholder="Search by feed code, description, PI code..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-1.5 text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z"></path></svg>
        </div>

      </div>

      <!-- FEED LIST DATA TABLE CARD -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Feed Code</th>
                <th class="py-3.5 px-6">Description</th>
                <th class="py-3.5 px-6">PI Code</th>
                <th class="py-3.5 px-6">Sold As</th>
                <th class="py-3.5 px-6 text-center">Status</th>
                <th class="py-3.5 px-6 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-red-600">ALL-PT-P-18</td>
                <td class="py-3.5 px-6">16% ALL PURPOSE RATION (WESOLOSKI) BARBADOS</td>
                <td class="py-3.5 px-6 font-mono text-slate-400">Ã¢â‚¬â€</td>
                <td class="py-3.5 px-6 font-semibold">PREMIUM ALL</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-PT-P-18" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">ALL-L-RB05</td>
                <td class="py-3.5 px-6">14% ALL PURPOSE CATTLE (WESOLOSKI) WITH BRAN, NO RICE</td>
                <td class="py-3.5 px-6 font-mono text-slate-400">Ã¢â‚¬â€</td>
                <td class="py-3.5 px-6 font-semibold">ALL-PUR</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-L-RB05" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">ALL-NB-09</td>
                <td class="py-3.5 px-6">14% ALL PURPOSE RATION</td>
                <td class="py-3.5 px-6 font-mono text-slate-400">Ã¢â‚¬â€</td>
                <td class="py-3.5 px-6 font-semibold">GENERAL PUR</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    Inactive
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-NB-09" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">ALL-P-NB14</td>
                <td class="py-3.5 px-6">14% ALL PURPOSE RATION WITHOUT RC/BRAN (WESOLOSKI)</td>
                <td class="py-3.5 px-6 font-mono font-bold text-slate-700">976</td>
                <td class="py-3.5 px-6 font-semibold">GENERAL PUR</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-P-NB14" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">ALL-P-NB15</td>
                <td class="py-3.5 px-6">14% ALL PURPOSE RATION WITHOUT RC/BRAN (WESOLOSKI)</td>
                <td class="py-3.5 px-6 font-mono font-bold text-slate-700">976</td>
                <td class="py-3.5 px-6 font-semibold">ALL PURPOSE</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-P-NB15" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-bold text-slate-900">ALL-P-P-18</td>
                <td class="py-3.5 px-6">16% ALL PURPOSE RATION (BARBADOS)</td>
                <td class="py-3.5 px-6 font-mono font-bold text-slate-700">978</td>
                <td class="py-3.5 px-6 font-semibold">Premium All pu</td>
                <td class="py-3.5 px-6 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                  </span>
                </td>
                <td class="py-3.5 px-6 text-right space-x-2">
                  <a href="edit_formula.php?code=ALL-P-P-18" class="text-slate-600 hover:text-red-600 font-semibold transition">Edit</a>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs font-medium text-slate-500">
          <span>Showing 6 of 14 Feed Formulas</span>
          <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
          <button type="button" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="First Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
          </button>
          <button type="button" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
          </button>
          <span class="px-3 font-semibold text-slate-700">Record 17149 of 17149</span>
          <button type="button" class="px-2 py-1 text-slate-400 cursor-not-allowed" disabled title="Next Sheet">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
          </button>
          <button type="button" class="px-2 py-1 text-slate-400 cursor-not-allowed" disabled title="New Sheet Record">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
          </button>
        </div>

      </div>

    </div>
  </main>

</body>
</html>
