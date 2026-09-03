function openAccountModal(account) {
  document.getElementById('edit-user-id').value = account.user_id;
  document.getElementById('edit-user-full-name').value = account.full_name;
  document.getElementById('edit-user-username').value = account.username;
  document.getElementById('edit-user-job-title').value = account.job_title || '';
  document.getElementById('edit-user-active').value = account.active_flag;
  document.getElementById('edit-user-admin').value = account.admin_flag;
  document.getElementById('edit-user-modal').classList.remove('hidden');
}

function openAuditModal(timestamp, ip, actor, action, details) {
  document.getElementById('audit-timestamp').innerText = timestamp;
  document.getElementById('audit-ip').innerText = ip;
  document.getElementById('audit-actor').innerText = actor;
  document.getElementById('audit-action').innerText = action;
  document.getElementById('audit-details').innerText = details;
  document.getElementById('audit-details-modal').classList.remove('hidden');
}

function openMillerModal(userId, fullName, jobTitle, activeFlag) {
  document.getElementById('edit-miller-user-id').value = userId;
  document.getElementById('edit-miller-full-name').value = fullName;
  document.getElementById('edit-miller-job-title').value = jobTitle;
  document.getElementById('edit-miller-status').value = activeFlag;
  document.getElementById('edit-miller-modal').classList.remove('hidden');
}

function openSupplierModal(supplier) {
  document.getElementById('edit-supplier-id').value = supplier.supplier_id;
  document.getElementById('edit-supplier-company-name').value = supplier.company_name;
  document.getElementById('edit-supplier-contact-person').value = supplier.contact_person;
  document.getElementById('edit-supplier-country').value = supplier.country;
  document.getElementById('edit-supplier-phone').value = supplier.phone;
  document.getElementById('edit-supplier-email').value = supplier.email;
  document.getElementById('edit-supplier-modal').classList.remove('hidden');
}