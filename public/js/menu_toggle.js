document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('sidebar-collapse-toggle');
  const sidebar = document.getElementById('sidebar');

  // 1. Sidebar Collapse / Expand Toggle
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth >= 768) {
        const isCollapsed = sidebar.classList.toggle('collapsed');

        // Toggle width styles
        if (isCollapsed) {
          sidebar.classList.remove('md:w-72');
          sidebar.classList.add('md:w-20');
        } else {
          sidebar.classList.remove('md:w-20');
          sidebar.classList.add('md:w-72');
        }

        // Close all floating menus immediately when switching view modes
        closeAllMenus();
      }
    });
  }

  // 2. Click-Outside Event: Close floating menus when clicking anywhere else on screen
  document.addEventListener('click', (e) => {
    if (sidebar && sidebar.classList.contains('collapsed')) {
      if (!sidebar.contains(e.target)) {
        closeAllMenus();
      }
    }
  });
});

// Helper function to close all dropdown menus
function closeAllMenus() {
  const allMenus = document.querySelectorAll('#sidebar-navigation div[id$="-menu"]');
  const allButtons = document.querySelectorAll('#sidebar-navigation button[aria-expanded]');

  allMenus.forEach(menu => {
    menu.hidden = true;
  });

  allButtons.forEach(button => {
    button.setAttribute('aria-expanded', 'false');
    const chevron = button.querySelector('.menu-chevron');
    if (chevron) {
      chevron.classList.remove('rotate-180');
    }
  });
}

// Menu Toggle Function
function toggleMenu(button, menuId) {
  const sidebar = document.getElementById('sidebar');
  const targetMenu = document.getElementById(menuId);
  if (!targetMenu) return;

  const isCollapsed = sidebar && sidebar.classList.contains('collapsed');
  const isCurrentlyOpen = !targetMenu.hidden;

  if (isCollapsed) {
    // COLLAPSED MODE: Only 1 floating menu allowed at a time
    closeAllMenus();

    // If it wasn't open previously, open it now
    if (!isCurrentlyOpen) {
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
    }
  } else {
    // EXPANDED MODE: Standard independent toggle (allows multiple open menus + scrolling)
    if (isCurrentlyOpen) {
      targetMenu.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      const chevron = button.querySelector('.menu-chevron');
      if (chevron) chevron.classList.remove('rotate-180');
    } else {
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
      const chevron = button.querySelector('.menu-chevron');
      if (chevron) chevron.classList.add('rotate-180');
    }
  }
}