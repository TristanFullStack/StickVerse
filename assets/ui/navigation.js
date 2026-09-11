// Without JavaScript every navigation link stays accessible.
const header = document.querySelector('[data-site-header]');
const toggle = header?.querySelector('[data-navigation-toggle]');
if (header && toggle) {
    header.classList.add('navigation-ready');
    toggle.hidden = false;
    const close = (restoreFocus = false) => {
        header.classList.remove('navigation-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus) toggle.focus();
    };
    toggle.addEventListener('click', () => {
        const open = header.classList.toggle('navigation-open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && header.classList.contains('navigation-open')) close(true);
    });
    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) close();
        else if (event.target.closest('a')) close();
    });
    window.matchMedia('(min-width: 1200px)').addEventListener('change', () => close());
}
// Preserve table semantics and keep horizontal scrolling local.
document.querySelectorAll('.site-content table').forEach((table) => {
    if (table.closest('#combat-en-ligne, #rapport-combat, .table-scroll, .classement-table-scroll')) return;
    const wrapper = document.createElement('div');
    wrapper.className = 'table-scroll';
    wrapper.tabIndex = 0;
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', 'Tableau — défilement horizontal si nécessaire');
    table.before(wrapper);
    wrapper.append(table);
});
