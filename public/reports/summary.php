<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Production Summary Report';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<style>
  @media print {
    body * {
      visibility: hidden;
    }
    #printable-content, #printable-content * {
      visibility: visible;
    }
    #printable-content {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
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

    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-1.5 z-10 shadow-xs">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Reports</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Production Summary Report</h1>
      </div>

      <!-- Date Range Controls & Print Trigger -->
      <div class="flex flex-wrap items-center gap-3">
        <form action="raw_materials_report.php" method="GET" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 text-xs">
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

    <!-- Printable Report Container -->
    <div id="printable-content" class="p-6 sm:p-8 max-w-7xl space-y-6">
      <!-- PRINT HEADER ONLY -->
      <div class="hidden print:block mb-6 border-b border-slate-300 pb-4">
        <h1 class="text-2xl font-black text-slate-900">EAST CARIBBEAN FEEDS</h1>
        <h2 class="text-base font-bold text-slate-600">Feed Production Summary & Variance Report</h2>
        <p class="text-xs text-slate-500">Reporting Period: 01/01/2026 To 08/01/2026</p>
      </div>

      <!-- MAIN DATA TABLE -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-900 text-white font-bold uppercase tracking-wider">
                <th class="py-3 px-4">Date</th>
                <th class="py-3 px-4">Sheet No</th>
                <th class="py-3 px-4 text-right">Target Usage (kg)</th>
                <th class="py-3 px-4 text-right">Actual Usage (kg)</th>
                <th class="py-3 px-4 text-right">Variance (kg)</th>
                <th class="py-3 px-4 text-right">Bags Produced</th>
                <th class="py-3 px-4 text-right">Variance (Bags)</th>
                <th class="py-3 px-4 text-right">Leftovers (Bags)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800 font-mono">

              <!-- ================= FEED TYPE 1 ================= -->
              <tr class="bg-slate-100/80 font-sans border-y border-slate-200">
                <td colspan="8" class="py-2.5 px-4 font-black text-red-600 tracking-wide text-sm">
                  FEEDTYPE: ALL-P-P-18
                </td>
              </tr>

              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">4/14/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135561</td>
                <td class="py-2 px-4 text-right">3,000.00</td>
                <td class="py-2 px-4 text-right">3,001.40</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+1.40</td>
                <td class="py-2 px-4 text-right">131.00</td>
                <td class="py-2 px-4 text-right text-red-600 font-bold">-1.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">6/29/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135948</td>
                <td class="py-2 px-4 text-right">10,000.00</td>
                <td class="py-2 px-4 text-right">0.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
                <td class="py-2 px-4 text-right">0.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">5/26/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135777</td>
                <td class="py-2 px-4 text-right">2,000.00</td>
                <td class="py-2 px-4 text-right">2,000.20</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+0.20</td>
                <td class="py-2 px-4 text-right">114.00</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+26.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">4/20/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135589</td>
                <td class="py-2 px-4 text-right">4,000.00</td>
                <td class="py-2 px-4 text-right">4,013.20</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+13.20</td>
                <td class="py-2 px-4 text-right">167.00</td>
                <td class="py-2 px-4 text-right text-red-600 font-bold">-9.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">5/29/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135807</td>
                <td class="py-2 px-4 text-right">5,000.00</td>
                <td class="py-2 px-4 text-right">5,011.40</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+11.40</td>
                <td class="py-2 px-4 text-right">220.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">5/22/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135759</td>
                <td class="py-2 px-4 text-right">500.00</td>
                <td class="py-2 px-4 text-right">500.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
                <td class="py-2 px-4 text-right">27.00</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+16.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>

              <!-- Subtotal ALL-P-P-18 -->
              <tr class="bg-red-50/60 font-bold font-sans border-y-2 border-red-200 text-slate-900">
                <td colspan="2" class="py-2.5 px-4 text-right uppercase text-[11px] font-black text-red-700">Subtotal (ALL-P-P-18):</td>
                <td class="py-2.5 px-4 text-right font-mono">70,000.00</td>
                <td class="py-2.5 px-4 text-right font-mono">60,113.20</td>
                <td class="py-2.5 px-4 text-right font-mono text-emerald-700">+113.20</td>
                <td class="py-2.5 px-4 text-right font-mono">2,667.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-emerald-700">+49.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-slate-500">0.00</td>
              </tr>

              <!-- ================= FEED TYPE 2 ================= -->
              <tr class="bg-slate-100/80 font-sans border-y border-slate-200">
                <td colspan="8" class="py-2.5 px-4 font-black text-red-600 tracking-wide text-sm">
                  FEEDTYPE: ALL-P-P-T-NB25
                </td>
              </tr>

              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">2/23/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135309</td>
                <td class="py-2 px-4 text-right">5,000.00</td>
                <td class="py-2 px-4 text-right">5,006.40</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+6.40</td>
                <td class="py-2 px-4 text-right">219.00</td>
                <td class="py-2 px-4 text-right text-red-600 font-bold">-1.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">1/23/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135146</td>
                <td class="py-2 px-4 text-right">6,000.00</td>
                <td class="py-2 px-4 text-right">6,008.60</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+8.60</td>
                <td class="py-2 px-4 text-right">254.00</td>
                <td class="py-2 px-4 text-right text-red-600 font-bold">-10.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2 px-4 text-slate-600">1/19/2026</td>
                <td class="py-2 px-4 font-semibold text-slate-900">A135111</td>
                <td class="py-2 px-4 text-right">7,000.00</td>
                <td class="py-2 px-4 text-right">7,028.40</td>
                <td class="py-2 px-4 text-right text-emerald-600 font-bold">+28.40</td>
                <td class="py-2 px-4 text-right">298.00</td>
                <td class="py-2 px-4 text-right text-red-600 font-bold">-10.00</td>
                <td class="py-2 px-4 text-right text-slate-400">0.00</td>
              </tr>

              <!-- Subtotal ALL-P-P-T-NB25 -->
              <tr class="bg-red-50/60 font-bold font-sans border-y-2 border-red-200 text-slate-900">
                <td colspan="2" class="py-2.5 px-4 text-right uppercase text-[11px] font-black text-red-700">Subtotal (ALL-P-P-T-NB25):</td>
                <td class="py-2.5 px-4 text-right font-mono">26,000.00</td>
                <td class="py-2.5 px-4 text-right font-mono">26,061.20</td>
                <td class="py-2.5 px-4 text-right font-mono text-emerald-700">+61.20</td>
                <td class="py-2.5 px-4 text-right font-mono">1,111.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-red-600">-33.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-slate-500">0.00</td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-100/80 font-sans border-y border-slate-200">
              <tr>
                <td colspan="2" class="py-2.5 px-4 text-right uppercase text-[11px] font-black text-red-700">Grand Total:</td>
                <td class="py-2.5 px-4 text-right font-mono">66,000.00</td>
                <td class="py-2.5 px-4 text-right font-mono">86,174.40</td>
                <td class="py-2.5 px-4 text-right font-mono text-emerald-700">+174.40</td>
                <td class="py-2.5 px-4 text-right font-mono">3,778.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-red-600">+16.00</td>
                <td class="py-2.5 px-4 text-right font-mono text-slate-500">0.00</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </div>
  </main>

</body>
</html>
