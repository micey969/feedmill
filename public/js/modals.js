function openMillerModal(userId, fullName, jobTitle, activeFlag) {
  document.getElementById('edit-miller-user-id').value = userId;
  document.getElementById('edit-miller-full-name').value = fullName;
  document.getElementById('edit-miller-job-title').value = jobTitle;
  document.getElementById('edit-miller-status').value = activeFlag;
  document.getElementById('edit-miller-modal').classList.remove('hidden');
}

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
  document.getElementById('modal-timestamp').innerText = timestamp;
  document.getElementById('modal-ip').innerText = ip;
  document.getElementById('modal-actor').innerText = actor;
  document.getElementById('modal-action').innerText = action;
  document.getElementById('modal-details').innerText = details;
  document.getElementById('audit-details-modal').classList.remove('hidden');
}