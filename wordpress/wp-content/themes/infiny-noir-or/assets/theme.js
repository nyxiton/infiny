(() => {
  const pairs = [['.menu-toggle', '#primary-nav'], ['.search-toggle', '#search-panel']];
  const close = () => pairs.forEach(([button, panel]) => {
    const trigger = document.querySelector(button), target = document.querySelector(panel);
    if (!trigger || !target) return;
    trigger.setAttribute('aria-expanded', 'false');
    target.classList.remove('is-open');
    if (panel === '#search-panel') target.hidden = true;
  });
  pairs.forEach(([button, panel]) => {
    const trigger = document.querySelector(button), target = document.querySelector(panel);
    if (!trigger || !target) return;
    trigger.addEventListener('click', () => {
      const open = trigger.getAttribute('aria-expanded') !== 'true';
      close();
      trigger.setAttribute('aria-expanded', String(open));
      target.classList.toggle('is-open', open);
      if (panel === '#search-panel') { target.hidden = !open; if (open) target.querySelector('input[type="search"]')?.focus(); }
    });
    target.addEventListener('click', event => { if (event.target.closest('a')) close(); });
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') { const active = document.querySelector('[aria-expanded="true"]'); close(); active?.focus(); } });
})();
