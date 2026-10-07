<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Feed Production Report';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<style>
  @media print {
    body * {
      visibility: hidden;
    }
    #printable-report, #printable-report * {
      visibility: visible;
    }
    #printable-report {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      border: none !important;
      box-shadow: none !important;
    }
    aside, header, .no-print {
      display: none !important;
    }
  }
</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <div class="h-1.5 shrink-0 bg-red-600 w-full sticky top-0 z-10"></div>

    <!-- Page Header Bar & Date Range Filter -->
    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-1.5 z-10 shadow-xs no-print">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Reports</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Feed Production Report</h1>
      </div>

      <!-- Date Range Controls & Print Trigger -->
      <div class="flex flex-wrap items-center gap-3">
        <form action="feed_production_report.php" method="GET" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 text-xs">
          <span class="text-slate-500 font-medium pl-2">Range:</span>
          <input type="date" name="start_date" value="2026-06-01" class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <span class="text-slate-400 font-medium">to</span>
          <input type="date" name="end_date" value="2026-06-28" class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-red-600">
          <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-xs">
            Go
          </button>
        </form>

        <button onclick="window.print()" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-300 transition" title="Print Report">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </button>
      </div>
    </header>


   <!-- Report View Workspace -->
    <div class="p-6 sm:p-8 max-w-5xl">
      
      <!-- REPORT SHEET CARD -->
      <div id="printable-report" class="bg-white rounded-3xl border border-slate-300 shadow-md p-8 sm:p-12 space-y-8">
        
        <!-- Report Title Block -->
        <div class="text-center space-y-2">
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Feed Production Report</h2>
          <p class="text-xs font-medium text-slate-500">From <span class="font-semibold text-slate-800">01-Jun-2026</span> to <span class="font-semibold text-slate-800">28-Jun-2026</span></p>
        </div>

        <!-- Data Table Grid -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Feed Type</th>
                <th class="py-3.5 px-6 text-right">Kgs</th>
                <th class="py-3.5 px-6 text-right">Tons</th>
                <th class="py-3.5 px-6 text-right">%</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-semibold">SOW-R-NB25</td>
                <td class="py-3.5 px-6 text-right font-mono">20,026.2</td>
                <td class="py-3.5 px-6 text-right font-mono">3</td>
                <td class="py-3.5 px-6 text-right font-mono">22.4</td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-semibold">SOW-R-NB25</td>
                <td class="py-3.5 px-6 text-right font-mono">20,026.2</td>
                <td class="py-3.5 px-6 text-right font-mono">8.03</td>
                <td class="py-3.5 px-6 text-right font-mono">0.49</td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-semibold">SOW-R-NB25</td>
                <td class="py-3.5 px-6 text-right font-mono">20,026.2</td>
                <td class="py-3.5 px-6 text-right font-mono">1</td>
                <td class="py-3.5 px-6 text-right font-mono">0.28</td>
              </tr>
              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6 font-mono font-semibold">SOW-R-NB25</td>
                <td class="py-3.5 px-6 text-right font-mono">20,026.2</td>
                <td class="py-3.5 px-6 text-right font-mono">20</td>
                <td class="py-3.5 px-6 text-right font-mono">2.23</td>
              </tr>
            </tbody>
            <!-- Table Footer: Grand Totals -->
            <tfoot>
              <tr class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <td class="py-4 px-6 uppercase tracking-wider">Grand Total</td>
                <td class="py-4 px-6 text-right font-mono text-sm">100,131</td>
                <td class="py-4 px-6 text-right font-mono text-sm">32.03</td>
                <td class="py-4 px-6 text-right"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Report Footer Meta -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-[11px] font-medium text-slate-400">
          <span>August 20, 2026</span>
          <span>Page 1 of 1</span>
        </div>

      </div>

    </div>
  </main>

</body>
</html>
