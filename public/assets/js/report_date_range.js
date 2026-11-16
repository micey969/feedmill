(() => {
  document.querySelectorAll('form').forEach((form) => {
    const startDate = form.querySelector('input[name="start_date"]');
    const endDate = form.querySelector('input[name="end_date"]');

    if (!startDate || !endDate) {
      return;
    }

    const updateDateConstraints = () => {
      startDate.max = endDate.value;
      endDate.min = startDate.value;
      const invalidRange = startDate.value && endDate.value && startDate.value > endDate.value;
      startDate.setCustomValidity(invalidRange ? 'Start date must be on or before the end date.' : '');
      endDate.setCustomValidity(invalidRange ? 'End date must be on or after the start date.' : '');
    };

    startDate.addEventListener('input', updateDateConstraints);
    startDate.addEventListener('change', updateDateConstraints);
    endDate.addEventListener('input', updateDateConstraints);
    endDate.addEventListener('change', updateDateConstraints);
    updateDateConstraints();
  });
})();
