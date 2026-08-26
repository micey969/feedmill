<?php
require_once __DIR__ . '/../app/views/auth/login.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ECGC Feeds System - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen text-slate-800 font-sans antialiased flex">

  <div class="w-full min-h-screen flex flex-col md:flex-row">
    
    <!-- ================= LEFT COLUMN: AUTHENTICATION FORM ================= -->
    <div class="w-full md:w-5/12 lg:w-4/12 bg-white flex flex-col justify-between p-8 sm:p-12 z-10 shadow-2xl relative">
      
      <!-- Top Brand Header -->
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <div class="bg-red-600 px-3 py-1 rounded-lg inline-block">
            <span class="text-2xl font-black text-white font-serif tracking-tighter">ECGC</span>
          </div>
          <div>
            <h1 class="text-xs font-bold text-slate-900 uppercase tracking-wider">East Caribbean Feeds</h1>
            <p class="text-[10px] font-semibold text-slate-400">Feedmill Production System</p>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-red-600 to-red-400 w-16 rounded-full mt-4"></div>
      </div>

      <!-- Center Login Form Area -->
      <div class="my-auto py-8 space-y-6">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">WELCOME BACK</h2>
          <p class="text-xs text-slate-500 font-medium mt-1">Please enter your credentials to access system management.</p>
        </div>

        <form action="login.php" method="POST" class="space-y-4">
          <!-- Username Input -->
          <div class="space-y-1.5">
            <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Username</label>
            <div class="relative">
              <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition shadow-xs">
              <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              </div>
            </div>
          </div>

          <!-- Password Input -->
          <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" placeholder="••••••••" required
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition shadow-xs">
              <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button type="submit" 
              class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-red-600/30 hover:shadow-red-600/40 transition duration-200 transform active:scale-[0.99] flex items-center justify-center gap-2">
              <span>Login to Workspace</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
          </div>
        </form>
      </div>

      <!-- Footer Info -->
      <div class="text-[11px] text-slate-400 font-medium flex items-center justify-between pt-6 border-t border-slate-100">
        <span>© 2026 ECGC Feeds</span>
        <span class="flex items-center gap-1.5 text-slate-500">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          System Online
        </span>
      </div>

    </div>

    <!-- ================= RIGHT COLUMN: VISUAL BACKDROP ================= -->
    <div class="hidden md:block w-7/12 lg:w-8/12 relative bg-slate-950 overflow-hidden">
      <!-- Background Image with Overlay -->
      <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?q=80&w=1600&auto=format&fit=crop" 
           alt="Feeds Mill Production" 
           class="absolute inset-0 w-full h-full object-cover object-center opacity-40 filter brightness-90">

      <!-- Gradient Overlay for Contrast -->
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>

      <!-- Floating System Metric Card Overlay -->
      <div class="absolute bottom-12 left-12 right-12 text-white max-w-xl space-y-4">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/20 text-xs font-semibold">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>
          <span>Feedmill Production Portal</span>
        </div>
        <h3 class="text-3xl font-black tracking-tight leading-tight">Precision Milling, Inventory Management, and Yield Tracking.</h3>
        <p class="text-xs text-slate-300 font-medium leading-relaxed">
          Authorized personnel access only. Manage mixing sheets, monitor raw material usagesand generate daily output logs seamlessly.
        </p>
      </div>
    </div>

  </div>

</body>
</html>