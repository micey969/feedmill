document.addEventListener('DOMContentLoaded', () => {
  const mainContent = document.getElementById('main-content');

  // Intercept clicks on sidebar sub-menu links
  document.querySelectorAll('#sidebar-navigation a').forEach(link => {
    link.addEventListener('click', async (e) => {
      const url = link.getAttribute('href');
      
      // Allow normal navigation for external links or empty anchors
      if (!url || url.startsWith('#') || url.startsWith('http')) return;

      e.preventDefault();

      try {
        const response = await fetch(url);
        const htmlText = await response.text();

        // Parse returned HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');
        const newContent = doc.getElementById('main-content');

        if (newContent && mainContent) {
          // Swap out only the main content inner HTML
          mainContent.innerHTML = newContent.innerHTML;

          // Push new URL to browser history
          history.pushState({ path: url }, '', url);

          // Update page title if present
          if (doc.title) document.title = doc.title;
        } else {
          // Fallback to regular navigation if #main-content isn't found
          window.location.href = url;
        }
      } catch (err) {
        console.error('Failed to load page dynamically:', err);
        window.location.href = url;
      }
    });
  });

  // Handle browser Back/Forward navigation buttons
  window.addEventListener('popstate', async () => {
    const response = await fetch(window.location.href);
    const htmlText = await response.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlText, 'text/html');
    const newContent = doc.getElementById('main-content');
    
    if (newContent && mainContent) {
      mainContent.innerHTML = newContent.innerHTML;
    }
  });
});