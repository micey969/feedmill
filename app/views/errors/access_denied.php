<!DOCTYPE html>
  <html lang="en">

  <!--  Dynamic Header  -->
  <?php 
    $pageTitle = 'Access Denied';
    require_once __DIR__ . '/../includes/head.php'
  ?>

  <body class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased flex flex-col md:flex-row">

    <!-- DYNAMIC SIDEBAR INCLUDE -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- ================= MAIN WORKSPACE ================= -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      <!-- Red Header Accent Line -->
      <div class="h-1.5 bg-red-600 w-full"></div>

      <!-- Page Header Bar -->
      <header class="bg-white border-b border-slate-200 px-6 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10 shadow-xs">
        <div>
          <nav class="flex items-center gap-2 text-xs text-slate-400 font-medium">
            <a href="index.php" class="hover:text-red-600 transition">Main Hub</a>
            <span>/</span>
            <span class="text-slate-600">Security Notice</span>
          </nav>
          <h1 class="text-lg font-bold text-slate-900">Access Restricted</h1>
        </div>
      </header>

      <!-- Workspace Body -->
      <div class="p-6 sm:p-12 flex-1 flex items-center justify-center">
      
        <!-- ACCESS DENIED CARD -->
        <div class="bg-white max-w-md w-full p-8 rounded-2xl border border-slate-200 shadow-xl text-center space-y-6">
            
            <!-- Shield Icon Badge -->
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center mx-auto shadow-inner">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>

            <!-- Text Notice -->
            <div class="space-y-2">
              <h2 class="text-2xl font-black text-slate-900">Access Denied</h2>
              <p class="text-xs font-medium text-slate-500 leading-relaxed">
                You do not have the required security permissions or access level to view this section. Please contact your system administrator if you believe this is an error.
              </p>
            </div>

            <!-- Action Button -->
            <div class="pt-2">
              <a href="<?php echo htmlspecialchars(publicUrl('index.php')); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-red-600/20 transition w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Back to Dashboard</span>
              </a>
            </div>
        </div>
      </div>
    </main>
  </body>
</html>