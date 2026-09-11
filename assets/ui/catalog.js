export function normalizeSearch(value) {
    return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr').trim();
}
document.querySelectorAll('[data-catalog]').forEach((catalog) => {
    const form = catalog.querySelector('[data-catalog-filters]');
    if (!form) return;
    const cards = [...catalog.querySelectorAll('[data-catalog-card]')];
    const count = form.querySelector('[data-catalog-count]');
    const empty = catalog.querySelector('[data-catalog-empty]');
    const collection = form.elements.namedItem('collection');
    const names = [...new Set(cards.map(card => card.dataset.collection).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'fr'));
    names.forEach(name => collection?.add(new Option(name, name)));
    const update = () => {
        const search = normalizeSearch(form.elements.namedItem('recherche').value);
        const rarity = form.elements.namedItem('rarete').value;
        let visible = 0;
        cards.forEach(card => {
            const matches = (!search || normalizeSearch(card.dataset.search).includes(search))
                && (!rarity || card.dataset.rarity === rarity)
                && (!collection?.value || card.dataset.collection === collection.value);
            card.hidden = !matches;
            if (matches) visible++;
        });
        count.textContent = `${visible} / ${cards.length} carte${cards.length > 1 ? 's' : ''}`;
        if (empty) empty.hidden = visible !== 0;
    };
    form.hidden = false;
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('reset', () => requestAnimationFrame(update));
    form.addEventListener('submit', event => event.preventDefault());
    update();
});
