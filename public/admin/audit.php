<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/middleware/admin_auth.php';

$recordsPerPage = 10;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$actionFilter = trim((string) ($_GET['action'] ?? ''));
$allowedActions = ['Login', 'Add', 'Update', 'Print', 'Logout', 'Timeout'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
  $dateFrom = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
  $dateTo = '';
}
if (!in_array($actionFilter, $allowedActions, true)) {
  $actionFilter = '';
}

$conditions = [];
$params = [];
$types = '';

if ($searchTerm !== '') {
  $conditions[] = '(ip_address LIKE ? OR username LIKE ? OR action_type LIKE ? OR details LIKE ?)';
  $searchParam = '%' . $searchTerm . '%';
  array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
  $types .= 'ssss';
}
if ($dateFrom !== '') {
  $conditions[] = 'time_stamp >= ?';
  $params[] = $dateFrom . ' 00:00:00';
  $types .= 's';
}
if ($dateTo !== '') {
  $conditions[] = 'time_stamp < DATE_ADD(?, INTERVAL 1 DAY)';
  $params[] = $dateTo;
  $types .= 's';
}

$actionPatterns = [
  'Login' => ['LOGIN%', 'AUTH%'],
  'Add' => ['ADD%'],
  'Update' => ['UPDATE%'],
  'Print' => ['PRINT%', 'EXPORT%'],
  'Logout' => ['LOGOUT%'],
  'Timeout' => ['TIMEOUT%'],
];
if ($actionFilter !== '') {
  $patterns = $actionPatterns[$actionFilter];
  $conditions[] = '(' . implode(' OR ', array_fill(0, count($patterns), 'action_type LIKE ?')) . ')';
  foreach ($patterns as $pattern) {
    $params[] = $pattern;
    $types .= 's';
  }
}

$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM audit_log' . $whereSql);
if ($types !== '') {
  $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRecords / $recordsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recordsPerPage;

$query = 'SELECT username, action_type, time_stamp, ip_address, details FROM audit_log' . $whereSql . ' ORDER BY time_stamp DESC LIMIT ? OFFSET ?';
$dataStmt = $conn->prepare($query);
$dataTypes = $types . 'ii';
$dataParams = array_merge($params, [$recordsPerPage, $offset]);
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$auditRecords = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dataStmt->close();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $exportStmt = $conn->prepare('SELECT username, action_type, time_stamp, ip_address, details FROM audit_log' . $whereSql . ' ORDER BY time_stamp DESC');
  if ($types !== '') {
    $exportStmt->bind_param($types, ...$params);
  }
  $exportStmt->execute();
  $exportRecords = $exportStmt->get_result();

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="audit-log-' . date('Y-m-d') . '.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Timestamp', 'IP Address', 'User', 'Action', 'Details']);
  while ($record = $exportRecords->fetch_assoc()) {
    fputcsv($output, [$record['time_stamp'], $record['ip_address'], $record['username'], $record['action_type'], $record['details']]);
  }
  fclose($output);
  $exportStmt->close();
  logAction($conn, $_SESSION['user'], "EXPORT", "Export Audit Logs");
  exit;
}

$displayStart = $totalRecords > 0 ? $offset + 1 : 0;
$displayEnd = min($offset + $recordsPerPage, $totalRecords);
$filterQuery = http_build_query(array_filter([
  'search' => $searchTerm,
  'date_from' => $dateFrom,
  'date_to' => $dateTo,
  'action' => $actionFilter,
], static fn ($value) => $value !== ''));
$pageUrl = static function (int $page) use ($filterQuery): string {
  return publicUrl('admin/audit.php') . '?page=' . $page . ($filterQuery !== '' ? '&' . $filterQuery : '');
};
$exportUrl = publicUrl('admin/audit.php') . '?export=csv' . ($filterQuery !== '' ? '&' . $filterQuery : '');

$actionStyles = [
  'ADD' => 'bg-amber-50 text-amber-700 border-amber-200',
  'UPDATE' => 'bg-blue-50 text-blue-700 border-blue-200',
  'EXPORT' => 'bg-gray-50 text-gray-700 border-gray-200',
  'PRINT' => 'bg-gray-50 text-gray-700 border-gray-200',
  'LOGOUT' => 'bg-red-50 text-red-700 border-red-200',
  'TIMEOUT' => 'bg-red-50 text-red-700 border-red-200',
  'LOGIN' => 'bg-green-50 text-green-700 border-green-200',
];
?>

<!DOCTYPE html>
<html lang="en">

<!-- Dynamic Head Component -->
<?php 
  $pageTitle = 'ECGC - System Audit Logs';
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
        <h1 class="text-lg font-bold text-slate-900">System Audit Trail</h1>
      </div>

      <div class="flex items-center gap-3">
        <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          <span>Export CSV</span>
        </a>
      </div>
    </header>

    <div class="p-6 sm:p-8 max-w-6xl space-y-4">
      
      <form method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative flex-1 min-w-[220px]">
            <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by IP, User, Action, or Details..." class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-1.5 text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z"></path></svg>
            <?php if ($searchTerm !== ''): ?>
              <a href="<?php echo htmlspecialchars(publicUrl('admin/audit.php')); ?>" aria-label="Clear search" title="Clear search" class="absolute right-0 top-2.5 pr-3 text-slate-400 hover:text-slate-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path></svg>
              </a>          
            <?php endif; ?>
          </div>

          <div class="flex items-center gap-2 text-xs">
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 font-medium text-slate-700">
            <span class="text-slate-400">to</span>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 font-medium text-slate-700">
          </div>

          <select name="action" class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">All Action Types</option>
            <option value="Login" <?php echo $actionFilter === 'Login' ? 'selected' : ''; ?>>Authentication</option>
            <option value="Add" <?php echo $actionFilter === 'Add' ? 'selected' : ''; ?>>Data Creation</option>
            <option value="Update" <?php echo $actionFilter === 'Update' ? 'selected' : ''; ?>>Data Modification</option>
            <option value="Print" <?php echo $actionFilter === 'Print' ? 'selected' : ''; ?>>Report Generation</option>
            <option value="Logout" <?php echo $actionFilter === 'Logout' ? 'selected' : ''; ?>>Session Termination</option>
            <option value="Timeout" <?php echo $actionFilter === 'Timeout' ? 'selected' : ''; ?>>Session Timeout</option>
          </select>
        </div>

        <span class="text-xs font-semibold text-slate-500">Showing <?php echo $totalRecords; ?> Log Entries</span>
        <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition">Apply</button>
      
      </form>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <th class="py-3.5 px-6">Timestamp & Date</th>
                <th class="py-3.5 px-6">IP Address</th>
                <th class="py-3.5 px-6">User</th>
                <th class="py-3.5 px-6">Action</th>
                <th class="py-3.5 px-6">Details</th>
                <th class="py-3.5 px-6 text-right">Inspect</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
              <?php if (!$auditRecords): ?>
                <tr><td colspan="6" class="py-6 px-6 text-center text-slate-500">No audit log entries match the selected filters.</td></tr>
              <?php else: ?>
                <?php foreach ($auditRecords as $record): ?>
                  <?php
                    $timestamp = (string) $record['time_stamp'];
                    $action = (string) $record['action_type'];
                    $actionPrefix = strtoupper((string) strtok($action, '_'));
                    $actionClass = $actionStyles[$actionPrefix] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                    $actor = (string) ($record['username'] ?? 'Unknown User');
                    $details = (string) ($record['details'] ?? '');
                    $modalPayload = htmlspecialchars(json_encode([$timestamp, $record['ip_address'], $actor, $action, $details], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                  ?>
                  <tr class="hover:bg-slate-50/50 transition">
                    <td class="py-3.5 px-6"><span class="font-bold text-slate-900 block"><?php echo htmlspecialchars(date('Y-m-d', strtotime($timestamp))); ?></span><span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars(date('h:i:s A', strtotime($timestamp))); ?></span></td>
                    <td class="py-3.5 px-6 font-mono text-slate-700"><?php echo htmlspecialchars($record['ip_address']); ?><span class="block text-[10px] text-slate-400">Recorded Origin</span></td>
                    <td class="py-3.5 px-6"><span class="font-bold text-slate-900"><?php echo htmlspecialchars($actor); ?></span><span class="block text-[10px] text-slate-400">Audited User</span></td>
                    <td class="py-3.5 px-6"><span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $actionClass; ?>"><span class="w-1.5 h-1.5 rounded-full bg-current"></span><?php echo htmlspecialchars($action); ?></span></td>
                    <td class="py-3.5 px-6 max-w-xs truncate whitespace-nowrap overflow-hidden text-ellipsistext-slate-600"><?php echo htmlspecialchars($details); ?></td>
                    <td class="py-3.5 px-6 text-right"><button type="button" onclick="openAuditModal(...<?php echo $modalPayload; ?>)" class="text-slate-600 hover:text-red-600 font-bold transition">View Details</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
          <span class="text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?php echo $displayStart; ?>-<?php echo $displayEnd; ?></span> of <span class="font-bold text-slate-800"><?php echo $totalRecords; ?></span> log entries</span>

          <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars($pageUrl(1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="First Sheet" aria-label="First page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed " title="First Sheet" aria-label="First page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
            <?php if ($currentPage > 1): ?></a><?php else: ?></button><?php endif; ?>

            <?php if ($currentPage > 1): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($currentPage - 1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Previous Sheet" aria-label="Previous page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Previous Sheet" aria-label="Previous page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            <?php if ($currentPage > 1): ?></a><?php else: ?></button><?php endif; ?>

            <span class="px-3 font-semibold text-slate-700">Records <?php echo $displayStart; ?>-<?php echo $displayEnd; ?> of <?php echo $totalRecords; ?></span>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($currentPage + 1)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Next Sheet" aria-label="Next page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Next Sheet" aria-label="Next page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <?php if ($currentPage < $totalPages): ?></a><?php else: ?></button><?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
              <a href="<?php echo htmlspecialchars($pageUrl($totalPages)); ?>" class="px-2 py-1 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition" title="Last Sheet" aria-label="Last page">
            <?php else: ?>
              <button type="button" disabled class="px-2 py-1 text-slate-400 cursor-not-allowed" title="Last Sheet" aria-label="Last page">
            <?php endif; ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
            <?php if ($currentPage < $totalPages): ?></a><?php else: ?></button><?php endif; ?>
          </div>
        </div>
      </div>
    </div>

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
              <span id="audit-timestamp" class="font-mono font-bold text-slate-800"></span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">IP Address</span>
              <span id="audit-ip" class="font-mono font-bold text-slate-800"></span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Actor / User</span>
              <span id="audit-actor" class="font-bold text-slate-800"></span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Action Code</span>
              <span id="audit-action" class="font-mono font-bold text-red-600"></span>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Event Details Payload</label>
            <div id="audit-details" class="bg-slate-900 text-slate-200 p-3 rounded-xl font-mono text-[11px] leading-relaxed"></div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
          <button onclick="document.getElementById('audit-details-modal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition">
            Close Window
          </button>
        </div>
      </div>
    </div>
  </main>
</body>
</html>