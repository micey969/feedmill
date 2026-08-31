document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('sidebar-collapse-toggle');
  const sidebar = document.getElementById('sidebar');
  const collapsedStorageKey = 'feedmill-sidebar-collapsed';

  const setSidebarCollapsed = (isCollapsed) => {
    if (!sidebar) return;

    sidebar.classList.toggle('collapsed', isCollapsed);
    if (isCollapsed) {
      sidebar.classList.remove('md:w-72');
      sidebar.classList.add('md:w-20');
    } else {
      sidebar.classList.remove('md:w-20');
      sidebar.classList.add('md:w-72');
    }
    localStorage.setItem(collapsedStorageKey, String(isCollapsed));
  };

  // 1. Restore state on load
  if (sidebar && window.innerWidth >= 768) {
    const isSavedCollapsed = localStorage.getItem(collapsedStorageKey) === 'true';
    setSidebarCollapsed(isSavedCollapsed);
  }

  // 2. Hamburger Toggle Button Handler
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (window.innerWidth >= 768) {
        const currentCollapsed = sidebar.classList.contains('collapsed');
        setSidebarCollapsed(!currentCollapsed);
        closeAllMenus();
      }
    });
  }

  // 3. Click-Outside Event for Collapsed Floating Menus
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

// Global Menu Toggle Function
function toggleMenu(button, menuId) {
  const sidebar = document.getElementById('sidebar');
  const targetMenu = document.getElementById(menuId);
  if (!targetMenu || !sidebar) return;

  const isCollapsed = sidebar.classList.contains('collapsed');
  const isCurrentlyOpen = !targetMenu.hidden;

  if (isCollapsed) {
    closeAllMenus();
    if (!isCurrentlyOpen) {
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
    }
  } else {
    if (isCurrentlyOpen) {
      targetMenu.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      const chevron = button.querySelector('.menu-chevron');
      if (chevron) chevron.classList.remove('rotate-180');
    } else {
      closeAllMenus();
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
      const chevron = button.querySelector('.menu-chevron');
      if (chevron) chevron.classList.add('rotate-180');
    }
  }
}