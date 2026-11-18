(function () {
  const storageKey = 'feedmill.failedForm';
  const errorNames = ['error', 'edit_error', 'conflict', 'transport_error', 'ingredient_error'];
  const currentUrl = new URL(window.location.href);
  const errorName = errorNames.find((name) => currentUrl.searchParams.has(name));
  const normalizePath = (path) => path.replace(/\.php$/, '');

  function readDraft() {
    const stored = sessionStorage.getItem(storageKey);
    return stored ? JSON.parse(stored) : null;
  }

  let draft;
  try {
    draft = readDraft();
  } catch (error) {
    console.error('Unable to read saved form values.', error);
  }

  if (draft && errorName && draft.action.endsWith('/inventory/orders_update')
      && normalizePath(currentUrl.pathname) === normalizePath(new URL(draft.page).pathname)) {
    const source = new URL(draft.page);
    const filters = ['page', 'search', 'status'];
    if (filters.some((name) => source.searchParams.get(name) !== currentUrl.searchParams.get(name))) {
      source.searchParams.set(errorName, currentUrl.searchParams.get(errorName));
      window.location.replace(source.href);
      return;
    }
  }

  function catalogKey(control) {
    const row = control.closest('.ingredient-catalog-row, .transport-catalog-row');
    if (!row) return null;
    const id = row.querySelector('input[name="ids[]"], input[name="transport_ids[]"]');
    return id ? id.value : null;
  }

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post' || event.defaultPrevented) return;

    const fields = [...form.elements]
      .filter((control) => control.name && !control.disabled
        && !['password', 'file', 'submit', 'button', 'reset', 'image'].includes(control.type))
      .map((control) => ({
        name: control.name,
        value: control.value,
        checked: control.type === 'checkbox' || control.type === 'radio' ? control.checked : null,
        row: catalogKey(control),
      }));
    const snapshot = {
      page: window.location.href,
      action: normalizePath(new URL(form.action, window.location.href).pathname),
      orderId: form.querySelector('input[name="order_id"]')?.value || null,
      fields,
      hasPassword: !!form.querySelector('input[type="password"]'),
    };
    try {
      sessionStorage.setItem(storageKey, JSON.stringify(snapshot));
    } catch (error) {
      console.error('Unable to preserve form values.', error);
      window.alert('Form values could not be preserved if this submission fails.');
    }
  });

  function showRecovery(fields, message, target) {
    const container = document.createElement('div');
    container.className = 'm-4 p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded-xl text-sm';
    container.setAttribute('role', 'alert');
    const text = document.createElement('p');
    text.textContent = message;
    const values = document.createElement('textarea');
    values.className = 'w-full mt-2 p-2 border rounded text-xs';
    values.rows = Math.min(12, Math.max(3, fields.length));
    values.readOnly = true;
    values.value = fields.filter((field) => field.checked !== false)
      .map((field) => `${field.name}: ${field.value}`).join('\n');
    container.append(text, values);
    (target || document.querySelector('main') || document.body).prepend(container);
  }

  function prepareRows(form, fields, action) {
    const count = (name) => fields.filter((field) => field.name === name).length;
    let container;
    let add;
    if (action.endsWith('/inventory/orders_save')) {
      container = form.querySelector('#ingredient-rows');
      add = form.querySelector('#add-ingredient-row');
    } else if (action.endsWith('/inventory/orders_update')) {
      container = form.querySelector('.ingredient-rows');
      add = form.querySelector('.add-ingredient-row');
    }
    if (container && add) {
      const desired = count('ingredients_id[]');
      while (container.children.length < desired) {
        const previous = container.children.length;
        add.click();
        if (container.children.length <= previous) throw new Error('Unable to restore order rows.');
      }
      while (container.children.length > desired) container.lastElementChild.remove();
    }
    if (action.endsWith('/products/formula_save')) {
      container = form.querySelector('#ingredients-list');
      const desired = count('ingredients[]');
      while (container.children.length < desired) {
        const previous = container.children.length;
        form.querySelector('#add-row-btn').click();
        if (container.children.length <= previous) throw new Error('Unable to restore formula rows.');
      }
      while (container.children.length > desired) container.lastElementChild.remove();
    } else if (action.endsWith('/products/formula_update')) {
      container = form.querySelector('#edit-ingredients-list');
      const desired = count('ingredients[]');
      while (container.children.length < desired) {
        const previous = container.children.length;
        window.addIngredientRow();
        if (container.children.length <= previous) throw new Error('Unable to restore formula rows.');
      }
      while (container.children.length > desired) container.lastElementChild.remove();
      const code = document.getElementById('edit-formula-code');
      const id = fields.find((field) => field.name === 'formula_id');
      if (code && id) code.value = id.value;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!draft) return;
    if (normalizePath(currentUrl.pathname) !== normalizePath(new URL(draft.page).pathname)) {
      sessionStorage.removeItem(storageKey);
      return;
    }
    const loginError = draft.action.endsWith('/login')
      && normalizePath(currentUrl.pathname) === draft.action
      && !!document.querySelector('[role="alert"]');
    if (!errorName && !loginError) {
      sessionStorage.removeItem(storageKey);
      return;
    }

    const forms = [...document.querySelectorAll('form[method="POST" i]')];
    const form = forms.find((candidate) =>
      normalizePath(new URL(candidate.action, window.location.href).pathname) === draft.action
      && (!draft.action.endsWith('/inventory/orders_update')
        || candidate.querySelector('input[name="order_id"]')?.value === draft.orderId));
    if (!form) {
      showRecovery(draft.fields, 'The original form is no longer available. Copy the submitted values below.');
      sessionStorage.removeItem(storageKey);
      return;
    }

    try {
      prepareRows(form, draft.fields, draft.action);
    } catch (error) {
      console.error('Unable to restore form rows.', error);
      showRecovery(draft.fields, 'The form could not be restored. Copy the submitted values below.');
      sessionStorage.removeItem(storageKey);
      return;
    }
    const controls = [...form.elements];
    const used = new Set();
    const missing = [];
    for (const field of draft.fields) {
      const control = controls.find((candidate) => !used.has(candidate) && candidate.name === field.name
        && catalogKey(candidate) === field.row);
      if (!control || control.disabled) {
        missing.push(field);
        continue;
      }
      used.add(control);
      if (field.checked !== null) {
        control.checked = field.checked;
      } else {
        if (control instanceof HTMLSelectElement && field.value !== ''
            && ![...control.options].some((option) => option.value === field.value)) {
          control.add(new Option(`Unavailable (${field.value})`, field.value));
        }
        control.value = field.value;
      }
    }

    controls.filter((control) => used.has(control) && control.type !== 'hidden')
      .forEach((control) => {
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
      });

    const modal = form.closest('.fixed');
    if (modal) {
      modal.classList.remove('hidden');
      const message = document.createElement('div');
      message.className = 'm-3 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-xs';
      message.setAttribute('role', 'alert');
      message.textContent = (document.querySelector('[role="alert"]')?.textContent.trim()
        || 'The form could not be saved. Check the entered values and try again.')
        + (draft.hasPassword ? ' Re-enter the password if needed.' : '');
      form.prepend(message);
    } else if (draft.hasPassword && !loginError) {
      const message = document.createElement('p');
      message.className = 'text-sm text-rose-700';
      message.textContent = 'Please re-enter your password.';
      form.prepend(message);
    }
    if (missing.length) {
      showRecovery(missing, 'Some submitted values could not be restored because the form changed. Copy them below.', form);
    }
    sessionStorage.removeItem(storageKey);
  });
})();
