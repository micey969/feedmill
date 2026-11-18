(() => {
  const previousValues = new WeakMap();
  const messages = new WeakMap();

  const isNonNegativeInput = (input) =>
    input instanceof HTMLInputElement && input.type === 'number' && input.min !== '' && Number(input.min) >= 0;

  const showMessage = (input) => {
    let message = messages.get(input);
    if (!message) {
      message = document.createElement('span');
      message.className = 'block text-xs text-red-600';
      message.setAttribute('role', 'status');
      message.textContent = 'Negative numbers are not allowed.';
      input.insertAdjacentElement('afterend', message);
      messages.set(input, message);
    }
    message.hidden = false;
  };

  document.addEventListener('focusin', (event) => {
    if (isNonNegativeInput(event.target)) {
      previousValues.set(event.target, event.target.valueAsNumber < 0 ? '' : event.target.value);
    }
  });

  document.addEventListener('keydown', (event) => {
    const input = event.target;
    if (isNonNegativeInput(input) && (event.key === '-' || event.key === 'Subtract') && !input.validity.badInput) {
      event.preventDefault();
      showMessage(input);
    }
  });

  const rejectNegativeText = (event, text) => {
    if (isNonNegativeInput(event.target) && /^\s*[-\u2212]/.test(text)) {
      event.preventDefault();
      showMessage(event.target);
    }
  };
  document.addEventListener('paste', (event) => {
    if (isNonNegativeInput(event.target) && event.clipboardData) {
      rejectNegativeText(event, event.clipboardData.getData('text'));
    }
  });
  document.addEventListener('drop', (event) => {
    if (isNonNegativeInput(event.target) && event.dataTransfer) {
      rejectNegativeText(event, event.dataTransfer.getData('text'));
    }
  });

  document.addEventListener('input', (event) => {
    const input = event.target;
    if (!isNonNegativeInput(input)) return;
    if (input.value !== '' && input.valueAsNumber < 0) {
      input.value = previousValues.get(input) ?? '';
      showMessage(input);
    } else if (!input.validity.badInput) {
      previousValues.set(input, input.value);
      const message = messages.get(input);
      if (message) message.hidden = true;
    }
  }, true);
})();
