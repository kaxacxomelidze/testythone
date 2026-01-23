document.addEventListener('DOMContentLoaded', () => {
  console.log('[header.js] loaded ✅');

  const activitiesBtn = document.getElementById('activitiesBtn');
  const activitiesMenu = document.getElementById('activitiesMenu');

  // Check duplicates (VERY common when using include/fetch)
  const btnCount = document.querySelectorAll('#activitiesBtn').length;
  const menuCount = document.querySelectorAll('#activitiesMenu').length;

  console.log('[header.js] activitiesBtn count:', btnCount);
  console.log('[header.js] activitiesMenu count:', menuCount);

  if (!activitiesBtn || !activitiesMenu) {
    console.log('[header.js] Dropdown elements NOT found ❌', { activitiesBtn, activitiesMenu });
    return;
  }

  function closeDropdown() {
    activitiesMenu.classList.remove('open');
    activitiesBtn.setAttribute('aria-expanded', 'false');
  }

  activitiesBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();

    const isOpen = activitiesMenu.classList.toggle('open');
    activitiesBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    console.log('[header.js] toggle dropdown:', isOpen ? 'OPEN ✅' : 'CLOSE ✅');
  });

  // Click outside closes it
  document.addEventListener('click', () => {
    closeDropdown();
  });

  // Escape closes it
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDropdown();
  });
});
