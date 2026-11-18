document.addEventListener('keydown', (event) => {
  const input = event.target;
  if (event.key !== 'Enter' || event.isComposing || !(input instanceof HTMLInputElement) || !input.form || input.form.hasAttribute('data-allow-enter-submit')) {
    return;
  }

  if (!['submit', 'button', 'reset', 'image', 'file'].includes(input.type)) {
    event.preventDefault();
  }
});
