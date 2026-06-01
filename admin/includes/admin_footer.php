    </div><!-- end page-content -->
  </div><!-- end main-content -->
</div><!-- end admin-layout -->

<div class="toast-container" id="toastContainer"></div>

<script>
// Notification panel toggle
const notifToggle = document.getElementById('notifToggle');
const notifPanel  = document.getElementById('notifPanel');
notifToggle?.addEventListener('click', e => {
  e.stopPropagation();
  notifPanel.classList.toggle('open');
});
document.addEventListener('click', e => {
  if (!notifPanel?.contains(e.target) && e.target !== notifToggle) {
    notifPanel?.classList.remove('open');
  }
});

// Mobile menu
const mobileBtn = document.getElementById('mobileMenuBtn');
if (mobileBtn) mobileBtn.style.display = 'flex';

// Toast
function showToast(msg, type = 'info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
window.showToast = showToast;

// Confirm delete
function confirmDelete(msg) {
  return confirm(msg || 'Are you sure you want to delete this?');
}
window.confirmDelete = confirmDelete;

// Auto-dismiss alerts
document.querySelectorAll('.alert-auto').forEach(el => setTimeout(() => el.remove(), 4000));
</script>
</body>
</html>
