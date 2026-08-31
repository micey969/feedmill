<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - System Audit Logs';
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
        <h1 class="text-lg font-bold text-slate-900">System Audit Trail</h1>
      </div>

      <div class="flex items-center gap-3">
        <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          <span>Export CSV</span>
        </button>
      </div>
    </header>

    <div class="p-6 sm:p-8 max-w-7xl space-y-6">
      
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative flex-1 min-w-[220px]">
            <input type="text" placeholder="Search by IP, User, Action, or Details..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-1.5 text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z"></path></svg>
          </div>

          <div class="flex items-center gap-2 text-xs">
            <input type="date" value="2026-08-01" class="bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 font-medium text-slate-700">
            <span class="text-slate-400">to</span>
            <input type="date" value="2026-08-22" class="bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 font-medium text-slate-700">
          </div>

          <select class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">All Action Types</option>
            <option value="auth">Authentication</option>
            <option value="create">Data Creation</option>
            <option value="update">Data Modification</option>
            <option value="delete">Deletion</option>
          </select>
        </div>

        <span class="text-xs font-semibold text-slate-500">Showing 4 Log Entries</span>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Timestamp & Date</th>
                <th class="py-3.5 px-6">IP Address</th>
                <th class="py-3.5 px-6">Actor / User</th>
                <th class="py-3.5 px-6">Action</th>
                <th class="py-3.5 px-6">Details</th>
                <th class="py-3.5 px-6 text-right">Inspect</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900 block">2026-08-22</span>
                  <span class="text-[10px] text-slate-400 font-mono">10:04:12 AM</span>
                </td>
                <td class="py-3.5 px-6 font-mono text-slate-700">
                  192.168.1.45
                  <span class="block text-[10px] text-slate-400">Internal Network</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900">Bob The Builder</span>
                  <span class="block text-[10px] text-slate-400">b.builder@ecgc.vc</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    CREATE_SUPPLIER
                  </span>
                </td>
                <td class="py-3.5 px-6 max-w-xs truncate text-slate-600">
                  Added new supplier <strong class="text-slate-900">Wesoloski Grain Corp</strong> with ID SUP-001.
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button onclick="openAuditModal('2026-08-22 10:04:12', '192.168.1.45', 'Bob The Builder', 'CREATE_SUPPLIER', 'Added new supplier Wesoloski Grain Corp (SUP-001). Contact: John Wesoloski.')" class="text-slate-600 hover:text-red-600 font-bold transition">View Details</button>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900 block">2026-08-22</span>
                  <span class="text-[10px] text-slate-400 font-mono">08:15:30 AM</span>
                </td>
                <td class="py-3.5 px-6 font-mono text-slate-700">
                  192.168.1.12
                  <span class="block text-[10px] text-slate-400">Internal Network</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900">John Doe</span>
                  <span class="block text-[10px] text-slate-400">j.doe@ecgc.vc</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    UPDATE_MIXING_SHEET
                  </span>
                </td>
                <td class="py-3.5 px-6 max-w-xs truncate text-slate-600">
                  Updated target batch weights for formula <strong class="text-slate-900">ALL-P-P-18</strong>.
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button onclick="openAuditModal('2026-08-22 08:15:30', '192.168.1.12', 'John Doe', 'UPDATE_MIXING_SHEET', 'Modified target batch quantities for Mixing Sheet A135561.')" class="text-slate-600 hover:text-red-600 font-bold transition">View Details</button>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900 block">2026-08-21</span>
                  <span class="text-[10px] text-slate-400 font-mono">04:45:10 PM</span>
                </td>
                <td class="py-3.5 px-6 font-mono text-slate-700">
                  203.0.113.195
                  <span class="block text-[10px] text-amber-600 font-bold">External WAN</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900">System Gateway</span>
                  <span class="block text-[10px] text-slate-400">Automated Job</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    EXPORT_REPORT
                  </span>
                </td>
                <td class="py-3.5 px-6 max-w-xs truncate text-slate-600">
                  Generated monthly <strong class="text-slate-900">Feed Production Summary PDF</strong> report.
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button onclick="openAuditModal('2026-08-21 16:45:10', '203.0.113.195', 'System Gateway', 'EXPORT_REPORT', 'Automated scheduled export of Feed Production Variance report.')" class="text-slate-600 hover:text-red-600 font-bold transition">View Details</button>
                </td>
              </tr>

              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900 block">2026-08-21</span>
                  <span class="text-[10px] text-slate-400 font-mono">01:20:00 PM</span>
                </td>
                <td class="py-3.5 px-6 font-mono text-slate-700">
                  192.168.1.88
                  <span class="block text-[10px] text-slate-400">Internal Network</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="font-bold text-slate-900">Unknown User</span>
                  <span class="block text-[10px] text-red-500 font-semibold">Failed Attempt</span>
                </td>
                <td class="py-3.5 px-6">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    AUTH_FAILURE
                  </span>
                </td>
                <td class="py-3.5 px-6 max-w-xs truncate text-slate-600">
                  Failed login attempt for user <strong class="text-slate-900">admin_root</strong> (Invalid password).
                </td>
                <td class="py-3.5 px-6 text-right">
                  <button onclick="openAuditModal('2026-08-21 13:20:00', '192.168.1.88', 'Unknown User', 'AUTH_FAILURE', '3 consecutive failed password login attempts for account admin_root.')" class="text-slate-600 hover:text-red-600 font-bold transition">View Details</button>
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <div id="audit-details-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
      
      <div class="p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-slate-900">Audit Trail Record Details</h2>
          <p class="text-[10px] text-slate-500">Full event payload and origin identification.</p>
        </div>
        <button onclick="document.getElementById('audit-details-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <div class="p-6 space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
          <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Timestamp</span>
            <span id="modal-timestamp" class="font-mono font-bold text-slate-800"></span>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase">IP Address</span>
            <span id="modal-ip" class="font-mono font-bold text-slate-800"></span>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Actor / User</span>
            <span id="modal-actor" class="font-bold text-slate-800"></span>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Action Code</span>
            <span id="modal-action" class="font-mono font-bold text-red-600"></span>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Event Details Payload</label>
          <div id="modal-details" class="bg-slate-900 text-slate-200 p-3 rounded-xl font-mono text-[11px] leading-relaxed"></div>
        </div>
      </div>

      <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
        <button onclick="document.getElementById('audit-details-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition">
          Close Window
        </button>
      </div>
    </div>
  </div>

  <script>
    function openAuditModal(timestamp, ip, actor, action, details) {
      document.getElementById('modal-timestamp').innerText = timestamp;
      document.getElementById('modal-ip').innerText = ip;
      document.getElementById('modal-actor').innerText = actor;
      document.getElementById('modal-action').innerText = action;
      document.getElementById('modal-details').innerText = details;
      document.getElementById('audit-details-modal').classList.remove('hidden');
    }
  </script>

</body>
</html>