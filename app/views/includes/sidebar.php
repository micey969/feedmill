<?php 
  //Get the current page name 
  $current_page = basename($_SERVER['PHP_SELF']);
  $userImage = trim($_SESSION['image_name'] ?? '');
  $userImagePath = $userImage !== ''
    ? (str_starts_with($userImage, 'images/') ? $userImage : 'images/' . basename($userImage))
    : 'images/TS007.jpg';
?>

<!-- ================= SIDEBAR NAVIGATION ================= -->
<aside class="w-full md:w-72 bg-slate-900 text-slate-300 flex flex-col justify-between shrink-0 min-h-screen border-r border-slate-800 overflow-y-auto">
  <div>
    <!-- Brand & Logo Header -->
    <div class="p-6 border-b border-slate-800 flex items-center justify-between">
      <a href="main.php" class="flex items-center gap-3">
        <div class="bg-white px-3 py-1 rounded-lg">
          <span class="text-2xl font-black text-red-600 font-serif tracking-tighter">ECGC</span>
        </div>
        <div>
          <h1 class="text-xs font-bold text-white uppercase tracking-wider">Feeds System</h1>
          <p class="text-[10px] text-slate-400">East Caribbean Feeds</p>
        </div>
      </a>
    </div>

    <!-- Navigation Links -->
    <nav class="p-4 space-y-6 text-xs font-medium">
      
      <!-- SECTION 1: PROCESSING -->
      <div>
        <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest flex justify-between items-center">
          Production
        </div>
        <div class="space-y-1">
          <a href="mixing.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Mixing Sheet</a>
          <a href="variance.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Materials Used</a>
          <a href="items.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Items Sold Separately</a>
        </div>
      </div>

      <!-- SECTION 2: PROCESSING -->
      <div>
        <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest flex justify-between items-center">
          Product Management
        </div>
        <div class="space-y-1">
          <a href="formulas.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Formulas</a>
          <a href="feedlist.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Formula List</a>
          <a href="physical.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Physical Stock</a>
        </div>
      </div>

      <!-- SECTION 3: Forms -->
      <div>
        <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest flex justify-between items-center">
          Inventory
        </div>
        <div class="space-y-1">
          <a href="suppliers.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Suppliers</a>
          <a href="transport.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Transportation</a>
          <a href="orders.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Orders</a>
          <a href="receive.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Receive</a>
        </div>
      </div>

      <!-- SECTION 4: Reports -->
      <div>
        <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest flex justify-between items-center">
          Reports
        </div>
        <div class="space-y-1">
          <a href="materials.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Raw Material</a>
          <a href="sold.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Items Sold Separately</a>
          <a href="feeds.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Feed Production</a>
          <a href="summary.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Production Summary</a>
        </div>
      </div>

      <!-- SECTION 5: System Admin -->
      <div>
        <div class="px-3 mb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
          Administrator
        </div>
        <div class="space-y-1">
          <a href="millers.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Millers</a>
          <a href="accounts.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">User Accounts (A)</a>
          <a href="audit.php" class="block px-3 py-2 rounded-lg hover:bg-slate-800 hover:text-white transition text-slate-400">Audit Logs (A)</a>
        </div>
      </div>

    </nav>
  </div>

<!-- User Card & Logout -->
  <div class="p-4 border-t border-slate-800 bg-slate-950/50 flex items-center justify-between">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-full bg-slate-600 text-white flex items-center justify-center font-bold text-xs">
        <img src="<?php echo htmlspecialchars($userImagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="User Avatar" class="w-full h-full object-cover rounded-full">
      </div>
      <div>
        <p class="text-xs font-semibold text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        <p class="text-[10px] text-slate-500"><?php echo htmlspecialchars($_SESSION['job_title']); ?></p>
      </div>
    </div>
    <a href="logout.php" class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-lg transition">Logout</a>
  </div>
</aside>