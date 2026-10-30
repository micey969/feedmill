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

  // 1. Restore the user's sidebar preference on desktop page loads
  if (sidebar && window.innerWidth >= 768) {
    const isCollapsed = localStorage.getItem(collapsedStorageKey) === 'true';
    setSidebarCollapsed(isCollapsed);
  }

  // Re-enable the width transition only after the initial state is painted
  if (sidebar) {
    requestAnimationFrame(() => requestAnimationFrame(() => sidebar.style.removeProperty('transition')));
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
        updateActiveLinks();
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

  // 4. Close floating menus only when in collapsed mode
  attachLinkCloseHandlers();

  // 5. Initial Active Link Highlight & Section Menu Expansion Pass
  updateActiveLinks();
});

// Close open menus before navigating to another page.
function attachLinkCloseHandlers() {
  document.querySelectorAll('#sidebar-navigation a').forEach(link => {
    link.addEventListener('click', () => {
      const sidebar = document.getElementById('sidebar');
      if (sidebar && sidebar.classList.contains('collapsed')) closeAllMenus();
    });
  });
}

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
  const chevron = button.querySelector('.menu-chevron');
  
  if (!targetMenu || !sidebar) return;

  const isCollapsed = sidebar.classList.contains('collapsed');
  const isCurrentlyOpen = !targetMenu.hidden;

  if (isCollapsed) {
    closeAllMenus();
    if (!isCurrentlyOpen) {
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
      if (chevron) chevron.classList.add('rotate-180');
    }
  } else {
    if (isCurrentlyOpen) {
      targetMenu.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      if (chevron) chevron.classList.remove('rotate-180');
    } else {
      closeAllMenus();
      targetMenu.hidden = false;
      button.setAttribute('aria-expanded', 'true');
      if (chevron) chevron.classList.add('rotate-180');
    }
  }
}

// Accurate Active Link Highlighting using DOM pathname resolution
function updateActiveLinks() {
  // Links may include /public while the browser URL may not (and has no .php), so compare folder/page only
  const pageKey = (path) => path.replace(/\.php$/, '').split('/').filter(Boolean).slice(-2).join('/');
  const currentPath = pageKey(window.location.pathname);
  const navLinks = document.querySelectorAll('#sidebar-navigation a[href]');

  navLinks.forEach(link => {
    const isCurrent = pageKey(link.pathname) === currentPath;

    if (isCurrent) {
      link.classList.add('bg-red-600', 'text-white', 'font-bold');
      link.classList.remove('hover:bg-slate-800', 'hover:text-white', 'text-slate-400', 'font-semibold');
    } else {
      link.classList.remove('bg-red-600', 'text-white', 'font-bold');
      link.classList.add('hover:bg-slate-800', 'hover:text-white', 'text-slate-400', 'font-semibold');
    }
  });

  updateSectionHeaderHighlights(currentPath);
}

// Keep active menu section expanded and highlight header icons
function updateSectionHeaderHighlights(currentUrl) {
  const sidebar = document.getElementById('sidebar');
  const isCollapsed = sidebar && sidebar.classList.contains('collapsed');

  const sections = {
    'production': ['production/mixing', 'production/variance', 'production/items'],
    'product': ['products/formulas', 'products/feedlist', 'products/physical'],
    'inventory': ['inventory/suppliers', 'inventory/order_history', 'inventory/orders', 'inventory/receive'],
    'reports': ['reports/materials', 'reports/sold', 'reports/feeds', 'reports/summary'],
    'administration': ['admin/millers', 'admin/accounts', 'admin/audit']
  };
  Object.entries(sections).forEach(([menuPrefix, pages]) => {
    
    const isSectionActive = pages.includes(currentUrl);
    const menuBtn = document.querySelector(`button[aria-controls="${menuPrefix}-menu"]`);

    if (menuBtn) {
      const menu = document.getElementById(`${menuPrefix}-menu`);

      if (isSectionActive && !isCollapsed && menu) {
        menu.hidden = false;
        menuBtn.setAttribute('aria-expanded', 'true');
        const chevron = menuBtn.querySelector('.menu-chevron');
        if (chevron) chevron.classList.add('rotate-180');
      }

      const iconSpan = menuBtn.querySelector('span:first-child');

      if (iconSpan) {
        if (isSectionActive) {
          iconSpan.className = 'bg-red-800 text-white px-1 py-0.5 rounded text-[9px] shrink-0 transition';
        } else {
          iconSpan.className = 'bg-slate-800 text-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0 transition';
        }
      }

    }
  });
}
