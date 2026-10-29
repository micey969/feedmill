(() => {
  const mainContent = document.getElementById('main-content');
  const pageHeader = mainContent?.querySelector('[data-report-page-header]');

  if (!mainContent || !pageHeader) {
    return;
  }

  const updateStickyOffset = () => {
    const headerTop = Number.parseFloat(getComputedStyle(pageHeader).top) || 0;
    const headerHeight = pageHeader.getBoundingClientRect().height;
    mainContent.style.setProperty('--report-table-sticky-top', `${headerTop + headerHeight}px`);
  };

  updateStickyOffset();
  new ResizeObserver(updateStickyOffset).observe(pageHeader);
})();