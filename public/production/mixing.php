<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - Mixing Sheet';
  require_once __DIR__ . '/../../app/views/includes/head.php'; 
?>

<style>
  @media print {
    body * {
      visibility: hidden;
    }
    #printable-sheet, #printable-sheet * {
      visibility: visible;
    }
    #printable-sheet {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      padding: 0;
      margin: 0;
      box-shadow: none !important;
      border: none !important;
    }
    .no-print {
      display: none !important;
    }
  }
</style>

<body class="bg-slate-100 h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row overflow-hidden">

  <!-- ================= SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/../../app/views/includes/sidebar.php'; ?>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main id="main-content" class="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen">
    <!-- Red Header Accent Line -->
    <div class="h-1.5 bg-red-600 w-full no-print"></div>

    <!-- Page Header Bar -->
    <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs no-print">
      <div>
        <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
          <a href="index.php" class="hover:text-red-600 transition">Main Hub</a>
          <span>/</span>
          <span class="text-slate-600">Production</span>
        </nav>
        <h1 class="text-lg font-bold text-slate-900">Mixing Sheet</h1>
      </div>

      <!-- Action Controls -->
      <div class="flex items-center gap-3">
        <!-- Record Navigation Toolbar -->
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

        <button onclick="window.print()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
          <span>Print Mixing Sheet</span>
        </button>
      </div>
    </header>

    <!-- Workspace Body / Sheet Form Container -->
    <div class="p-6 sm:p-8 max-w-5xl space-y-6">
      
      <!-- PRINTABLE SHEET CONTAINER CARD -->
      <div id="printable-sheet" class="bg-white p-8 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        
        <!-- Sheet Top Header -->
        <div class="text-center border-b border-slate-200 pb-4">
          <h2 class="text-xl font-bold text-slate-900 tracking-tight">East Caribbean Feeds Limited</h2>
          <h3 class="text-2xl font-black text-red-600 uppercase tracking-wide mt-0.5">Mixing Sheet</h3>
        </div>

        <!-- FORM CONTROLS & META DATA GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-xl border border-slate-200">
          
          <!-- Left Column Inputs -->
          <div class="space-y-4">
            <!-- Tonnes Radio Selection -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Select Tonnes Per Batch</label>
              <div class="flex items-center gap-4 text-xs font-semibold">
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input type="radio" name="batch_tonnes" value="0.5" class="text-red-600 focus:ring-red-600">
                  <span>1/2 TON</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input type="radio" name="batch_tonnes" value="1.0" class="text-red-600 focus:ring-red-600">
                  <span>1 TON</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                  <input type="radio" name="batch_tonnes" value="2.0" checked class="text-red-600 focus:ring-red-600">
                  <span>2 TON</span>
                </label>
              </div>
            </div>

            <!-- Date Selection -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date</label>
              <input type="text" value="Sunday, June 28, 2026" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
          </div>

          <!-- Right Column Inputs -->
          <div class="space-y-4">
            <!-- Sheet No & Formula Code -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sheet No.</label>
                <input type="text" value="A135953" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-600">
              </div>
              <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Feed Code</label>
                <select class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-red-600 focus:outline-none focus:ring-2 focus:ring-red-600">
                  <option selected>B-GR-RS-08</option>
                  <option>HORSE-NB18</option>
                  <option>ALL-PT-P-18</option>
                </select>
              </div>
            </div>

            <!-- Product Description -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Product Description</label>
              <input type="text" value="BROILER GROWER WITH REDUCED SOYA MEAL( WESOLOSK" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
          </div>

        </div>

        <!-- MIXING TABLE WITH BATCH CHECKLIST GRID -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b-2 border-slate-300 text-slate-600 font-bold uppercase tracking-wider">
                <th class="py-2 px-3 text-right w-24">Per Batch</th>
                <th class="py-2 px-3">Ingredients</th>
                <th class="py-2 px-3 text-right w-28">Scale Readings</th>
                <th class="py-2 px-3 text-center w-36">
                  Batches<br><span class="text-[10px] font-normal text-slate-500">TWO TON</span>
                </th>
                <th class="py-2 px-3 text-right w-28">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-medium text-slate-800">

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">611.60</td>
                <td class="py-2 px-3 font-bold text-slate-900">CORN</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">611.60</td>
                <!-- Batch Checkboxes for Physical Printout -->
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">3,058.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">1,200.00</td>
                <td class="py-2 px-3 font-bold text-slate-900">SOYAMEAL</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">1,811.60</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">6,000.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">44.40</td>
                <td class="py-2 px-3 font-bold text-slate-900">LIMESTONE</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">1,856.00</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">222.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">60.00</td>
                <td class="py-2 px-3 font-bold text-slate-900">FISHMEAL</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">1,916.00</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">300.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">60.00</td>
                <td class="py-2 px-3 font-bold text-slate-900">BROILER PX 1%</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">1,976.00</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">300.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">20.00</td>
                <td class="py-2 px-3 font-bold text-slate-900">DICAL</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">1,996.00</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">100.00</td>
              </tr>

              <tr>
                <td class="py-2 px-3 text-right font-mono font-bold">4.00</td>
                <td class="py-2 px-3 font-bold text-slate-900">SALT</td>
                <td class="py-2 px-3 text-right font-mono text-slate-600">2,000.00</td>
                <td class="py-2 px-3 text-center">
                  <div class="inline-flex gap-1 justify-center">
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                    <span class="w-5 h-5 border border-slate-400 rounded-sm inline-block"></span>
                  </div>
                </td>
                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">20.00</td>
              </tr>

            </tbody>
            <!-- Table Subtotals -->
            <tfoot>
              <tr class="border-t-2 border-slate-300 font-bold text-slate-900">
                <td class="py-2.5 px-3 text-right font-mono text-sm">2,000.00</td>
                <td class="py-2.5 px-3 uppercase tracking-wider">TOTALS</td>
                <td class="py-2.5 px-3 text-right font-mono text-sm">2,000.00</td>
                <td></td>
                <td class="py-2.5 px-3 text-right font-mono text-sm text-red-600">10,000.00</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- FOOTER DETAILS & SIGNATURE SECTION -->
        <div class="pt-4 border-t border-slate-200 space-y-6">
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
            <div class="flex items-center gap-2">
              <span class="text-slate-500 uppercase tracking-wider text-[10px]">Required Production:</span>
              <input type="text" value="10" class="w-16 bg-slate-50 border border-slate-300 rounded px-2 py-1 text-center font-mono font-bold">
              <span>TONNES</span>
            </div>
            <div class="flex items-center gap-2 sm:justify-end">
              <span class="text-slate-500 uppercase tracking-wider text-[10px]">To Bin No:</span>
              <input type="text" class="w-24 bg-slate-50 border border-slate-300 rounded px-2 py-1 font-mono font-bold">
            </div>
          </div>

          <!-- Micro Reference & Notes -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Micro Reference Number</label>
              <textarea rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium focus:bg-white focus:ring-1 focus:ring-red-600"></textarea>
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Notes</label>
              <textarea rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-xs font-medium focus:bg-white focus:ring-1 focus:ring-red-600"></textarea>
            </div>
          </div>

          <!-- Mill Personnel Sign-off Boxes -->
          <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Miller Name</label>
              <input type="text" value="Mezorio Matthews" class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 font-medium">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Position</label>
              <input type="text" value="Asst. Mill Supervisor" class="w-full bg-slate-50 border border-slate-200 rounded px-2.5 py-1.5 font-medium">
            </div>
            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Scale Man Signature</label>
              <div class="h-9 border border-dashed border-slate-300 rounded bg-slate-50 flex items-center justify-center text-slate-400 text-[10px] font-mono">
                [ Sign Here ]
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex items-center justify-between no-print">
          <?php if ($isNewEntry): ?>
            <div class="flex items-center gap-3">
              <button type="reset" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 transition">Undo Changes</button>
              <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Save Mixing Sheet</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
