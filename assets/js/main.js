const sidebarToggle = document.querySelector('.sidebar-toggle');
const sidebar = document.querySelector('#sidebar');

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
}

document.querySelectorAll('.flash').forEach((flash) => {
  window.setTimeout(() => {
    flash.classList.add('hide');
  }, 4500);
});

document.querySelectorAll('button[value="reject"]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!window.confirm('هل تريد رفض هذا المشروع؟')) {
      event.preventDefault();
    }
  });
});
