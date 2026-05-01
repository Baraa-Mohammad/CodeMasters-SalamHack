document.querySelectorAll('form').forEach((form) => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button) {
      button.classList.add('loading');
      button.dataset.originalText = button.textContent.trim();
      button.textContent = 'جاري التنفيذ…';
    }
  });
});
