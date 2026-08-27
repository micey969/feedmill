<?php 
  //Get the current page name 
  $current_page = basename($_SERVER['PHP_SELF']);
  $isAdmin = (int) ($_SESSION['admin_flag'] ?? 0) === 1;
  $userImage = TRIM($_SESSION['image_name'] ?? '');
  $userImagePath = $userImage !== ''
    ? (str_starts_with($userImage, 'images/') ? $userImage : 'images/' . basename($userImage))
    : 'images/TS007.jpg';
?>

<!-- ================= SIDEBAR NAVIGATION ================= -->
<aside id="sidebar" class="group/sidebar w-full md:w-72 bg-slate-900 text-slate-300 flex flex-col justify-between shrink-0 h-screen border-r border-slate-800 transition-all duration-300 relative z-20">
  <div class="flex-1 flex flex-col min-h-0">
    <!-- Brand & Logo Header -->
    <div class="p-6 border-b border-slate-800 flex items-center justify-between group-[.collapsed]/sidebar:md:p-4 group-[.collapsed]/sidebar:md:justify-center">
      <a href="main.php" class="flex items-center gap-3 group-[.collapsed]/sidebar:md:hidden">
        <div class="bg-white px-1 rounded-lg shrink-0">
          <img src="images/logo.png" alt="ECGC Logo" class="w-10 h-8 object-contain">
        </div>
        <div>
          <h1 class="text-xs font-bold text-white uppercase tracking-wider">Feeds System</h1>
          <p class="text-[10px] text-slate-400">East Caribbean Feeds</p>
        </div>
      </a>
      <button id="sidebar-collapse-toggle" type="button" class="text-slate-400 hover:text-white transition p-1.5 rounded-lg hover:bg-slate-800 shrink-0" aria-label="Toggle sidebar">
        <svg class="size-5 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>
    </div>

    <!-- Navigation Links -->
    <nav id="sidebar-navigation" class="p-4 space-y-6 text-xs font-medium overflow-y-auto flex-1 group-[.collapsed]/sidebar:md:overflow-visible group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:space-y-4">
      
      <!-- SECTION 1: Production -->
      <div class="relative group/menu">
        <button type="button" class="w-full px-3 py-1 mb-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest flex gap-3 items-center rounded-lg hover:bg-slate-800/50 group-[.collapsed]/sidebar:md:mb-0 group-[.collapsed]/sidebar:md:justify-center group-[.collapsed]/sidebar:md:p-2" aria-expanded="false" aria-controls="production-menu" onclick="toggleMenu(this, 'production-menu')">
          <span class="bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
          </span>
          <span class="flex-1 group-[.collapsed]/sidebar:md:hidden">Production</span>
          <svg class="menu-chevron size-4 transition-transform group-[.collapsed]/sidebar:md:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
          </svg>
        </button>
        <div id="production-menu" class="space-y-1 group-[.collapsed]/sidebar:md:absolute group-[.collapsed]/sidebar:md:left-full group-[.collapsed]/sidebar:md:top-0 group-[.collapsed]/sidebar:md:ml-3 group-[.collapsed]/sidebar:md:w-56 group-[.collapsed]/sidebar:md:bg-slate-900 group-[.collapsed]/sidebar:md:border group-[.collapsed]/sidebar:md:border-slate-800 group-[.collapsed]/sidebar:md:rounded-xl group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:shadow-2xl" hidden>
          <a href="mixing.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Mixing Sheet</a>
          <a href="variance.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Materials Used</a>
          <a href="items.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Items Sold Separately</a>
        </div>
      </div>

      <!-- SECTION 2: Product Management -->
      <div class="relative group/menu">
        <button type="button" class="w-full px-3 py-1 mb-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest flex gap-3 items-center rounded-lg hover:bg-slate-800/50 group-[.collapsed]/sidebar:md:mb-0 group-[.collapsed]/sidebar:md:justify-center group-[.collapsed]/sidebar:md:p-2" aria-expanded="false" aria-controls="product-menu" onclick="toggleMenu(this, 'product-menu')">
          <span class="bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
          </span>
          <span class="flex-1 group-[.collapsed]/sidebar:md:hidden">Product Management</span>
          <svg class="menu-chevron size-4 transition-transform group-[.collapsed]/sidebar:md:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div id="product-menu" class="space-y-1 group-[.collapsed]/sidebar:md:absolute group-[.collapsed]/sidebar:md:left-full group-[.collapsed]/sidebar:md:top-0 group-[.collapsed]/sidebar:md:ml-3 group-[.collapsed]/sidebar:md:w-56 group-[.collapsed]/sidebar:md:bg-slate-900 group-[.collapsed]/sidebar:md:border group-[.collapsed]/sidebar:md:border-slate-800 group-[.collapsed]/sidebar:md:rounded-xl group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:shadow-2xl" hidden>
          <a href="formulas.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Formulas</a>
          <a href="feedlist.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Formula List</a>
          <a href="physical.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Physical Stock</a>
        </div>
      </div>

      <!-- SECTION 3: Inventory -->
      <div class="relative group/menu">
        <button type="button" class="w-full px-3 py-1 mb-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest flex gap-3 items-center rounded-lg hover:bg-slate-800/50 group-[.collapsed]/sidebar:md:mb-0 group-[.collapsed]/sidebar:md:justify-center group-[.collapsed]/sidebar:md:p-2" aria-expanded="false" aria-controls="inventory-menu" onclick="toggleMenu(this, 'inventory-menu')">
          <span class="bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
            </svg>
          </span>
          <span class="flex-1 group-[.collapsed]/sidebar:md:hidden">Inventory</span>
          <svg class="menu-chevron size-4 transition-transform group-[.collapsed]/sidebar:md:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div id="inventory-menu" class="space-y-1 group-[.collapsed]/sidebar:md:absolute group-[.collapsed]/sidebar:md:left-full group-[.collapsed]/sidebar:md:top-0 group-[.collapsed]/sidebar:md:ml-3 group-[.collapsed]/sidebar:md:w-56 group-[.collapsed]/sidebar:md:bg-slate-900 group-[.collapsed]/sidebar:md:border group-[.collapsed]/sidebar:md:border-slate-800 group-[.collapsed]/sidebar:md:rounded-xl group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:shadow-2xl" hidden>
          <a href="suppliers.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Suppliers</a>
          <a href="transport.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Transportation</a>
          <a href="orders.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Orders</a>
          <a href="receive.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Receive</a>
        </div>
      </div>

      <!-- SECTION 4: Reports -->
      <div class="relative group/menu">
        <button type="button" class="w-full px-3 py-1 mb-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest flex gap-3 items-center rounded-lg hover:bg-slate-800/50 group-[.collapsed]/sidebar:md:mb-0 group-[.collapsed]/sidebar:md:justify-center group-[.collapsed]/sidebar:md:p-2" aria-expanded="false" aria-controls="reports-menu" onclick="toggleMenu(this, 'reports-menu')">
          <span class="bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
          </span>
          <span class="flex-1 group-[.collapsed]/sidebar:md:hidden">Reports</span>
          <svg class="menu-chevron size-4 transition-transform group-[.collapsed]/sidebar:md:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>
        <div id="reports-menu" class="space-y-1 group-[.collapsed]/sidebar:md:absolute group-[.collapsed]/sidebar:md:left-full group-[.collapsed]/sidebar:md:top-0 group-[.collapsed]/sidebar:md:ml-3 group-[.collapsed]/sidebar:md:w-56 group-[.collapsed]/sidebar:md:bg-slate-900 group-[.collapsed]/sidebar:md:border group-[.collapsed]/sidebar:md:border-slate-800 group-[.collapsed]/sidebar:md:rounded-xl group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:shadow-2xl" hidden>
          <a href="materials.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Raw Material</a>
          <a href="sold.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Items Sold Separately</a>
          <a href="feeds.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Feed Production</a>
          <a href="summary.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Production Summary</a>
        </div>
      </div>

      <!-- SECTION 5: Administration -->
      <div class="relative group/menu">
        <button type="button" class="w-full px-3 py-1 mb-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-widest flex gap-3 items-center rounded-lg hover:bg-slate-800/50 group-[.collapsed]/sidebar:md:mb-0 group-[.collapsed]/sidebar:md:justify-center group-[.collapsed]/sidebar:md:p-2" aria-expanded="false" aria-controls="administration-menu" onclick="toggleMenu(this, 'administration-menu')">
          <span class="bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
          </span>
          <span class="flex-1 group-[.collapsed]/sidebar:md:hidden">Administration</span>
          <svg class="menu-chevron size-4 transition-transform group-[.collapsed]/sidebar:md:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
          </svg>
        </button>
        <div id="administration-menu" class="space-y-1 group-[.collapsed]/sidebar:md:absolute group-[.collapsed]/sidebar:md:left-full group-[.collapsed]/sidebar:md:top-0 group-[.collapsed]/sidebar:md:ml-3 group-[.collapsed]/sidebar:md:w-56 group-[.collapsed]/sidebar:md:bg-slate-900 group-[.collapsed]/sidebar:md:border group-[.collapsed]/sidebar:md:border-slate-800 group-[.collapsed]/sidebar:md:rounded-xl group-[.collapsed]/sidebar:md:p-2 group-[.collapsed]/sidebar:md:shadow-2xl" hidden>
          <a href="millers.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Millers</a>
          
          <?php if($isAdmin): ?>
          <a href="accounts.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">User Accounts</a>
          <a href="audit.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Audit Logs</a>
          <?php endif; ?>
        </div>
      </div>

    </nav>
  </div>

  <!-- User Card & Logout -->
  <div class="sidebar-user-info p-4 border-t border-slate-800 bg-slate-950/50 flex items-center justify-between group-[.collapsed]/sidebar:md:p-3 group-[.collapsed]/sidebar:md:justify-center">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-full bg-slate-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
        <img src="<?php echo htmlspecialchars($userImagePath); ?>" alt="User Avatar" class="w-full h-full object-cover rounded-full">
      </div>
      <div class="sidebar-user-details group-[.collapsed]/sidebar:md:hidden">
        <p class="text-xs font-semibold text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        <p class="text-[10px] text-slate-500"><?php echo htmlspecialchars($_SESSION['job_title']); ?></p>
      </div>
    </div>
    <a href="logout.php" class="sidebar-logout px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-lg transition group-[.collapsed]/sidebar:md:hidden">Logout</a>
  </div>
</aside>

<script src="js/menu_toggle.js"></script>