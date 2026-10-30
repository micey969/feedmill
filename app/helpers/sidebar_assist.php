<?php 
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $current_page = ltrim(substr($scriptName, strlen(PUBLIC_URL)), '/');

  //Make the sidebar link active if the current page matches the link's page
  function sidebarLinkClass(string $page, string $currentPage): string {
    $baseClass = 'block px-3 py-2 rounded-lg transition';
    return $page === $currentPage
      ? $baseClass . ' bg-red-600 text-white'
      : $baseClass . ' hover:bg-slate-800 hover:text-white font-semibold text-slate-400';
  }

  //Make the sidebar icon active if the current page matches any of the link's pages
  function sidebarIconClass(array $pages, string $currentPage): string {
    $baseClass = 'bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0';
    return in_array($currentPage, $pages, true)
      ? 'bg-red-800 text-white px-1 py-0.5 rounded text-[9px] shrink-0'
      : $baseClass;
  }

  $productionPages = ['production/mixing', 'production/variance', 'production/items'];
  $productPages = ['products/formulas', 'products/feedlist', 'products/physical'];
  $inventoryPages = ['inventory/suppliers', 'inventory/order_history', 'inventory/orders', 'inventory/receive'];
  $reportPages = ['reports/materials', 'reports/sold', 'reports/feeds', 'reports/summary'];
  $administrationPages = ['admin/millers', 'admin/accounts', 'admin/audit'];
  
  $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
  $isUser = ($_SESSION['role'] ?? '') === 'user';
  
  $userImage = TRIM($_SESSION['image_name'] ?? '');
  $userImagePath = $userImage !== ''
    ? publicUrl(str_starts_with($userImage, 'assets/images/') ? $userImage : 'assets/images/' . basename($userImage))
    : publicUrl('assets/images/avatar.png');