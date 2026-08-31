<?php 
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $current_page = ltrim(substr($scriptName, strlen(PUBLIC_URL)), '/');

  //Make the sidebar link active if the current page matches the link's page
  function sidebarLinkClass(string $page, string $currentPage): string {
    $baseClass = 'block px-3 py-2 rounded-lg transition';
    return $page === $currentPage
      ? $baseClass . ' bg-red-600 text-white'
      : $baseClass . ' hover:bg-slate-800 hover:text-white text-slate-400';
  }

  //Make the sidebar icon active if the current page matches any of the link's pages
  function sidebarIconClass(array $pages, string $currentPage): string {
    $baseClass = 'bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0';
    return in_array($currentPage, $pages, true)
      ? $baseClass . ' group-[.collapsed]/sidebar:md:bg-red-800 group-[.collapsed]/sidebar:md:text-white'
      : $baseClass;
  }

  $productionPages = ['production/mixing.php', 'production/variance.php', 'production/items.php'];
  $productPages = ['products/formulas.php', 'products/feedlist.php', 'products/physical.php'];
  $inventoryPages = ['inventory/suppliers.php', 'inventory/transport.php', 'inventory/orders.php', 'inventory/receive.php'];
  $reportPages = ['reports/materials.php', 'reports/sold.php', 'reports/feeds.php', 'reports/summary.php'];
  $administrationPages = ['admin/millers.php', 'admin/accounts.php', 'admin/audit.php'];
  
  $isAdmin = (int) ($_SESSION['admin_flag'] ?? 0) === 1;
  
  $userImage = TRIM($_SESSION['image_name'] ?? '');
  $userImagePath = $userImage !== ''
    ? publicUrl(str_starts_with($userImage, 'images/') ? $userImage : 'images/' . basename($userImage))
    : publicUrl('images/avatar.png');