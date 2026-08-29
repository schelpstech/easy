(() => {
  'use strict';

  const menu = document.querySelector('[data-site-menu]');
  const toggle = document.querySelector('[data-site-menu-toggle]');
  if (menu && toggle) {
    toggle.addEventListener('click', () => {
      const open = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  document.querySelectorAll('[data-menu-group-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const group = button.closest('.stage1-menu-group');
      const open = group?.classList.toggle('is-open') ?? false;
      button.setAttribute('aria-expanded', String(open));
    });
  });

  document.querySelectorAll('[data-tracking-input]').forEach((input) => {
    input.addEventListener('input', () => {
      input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 19);
    });
  });
})();

