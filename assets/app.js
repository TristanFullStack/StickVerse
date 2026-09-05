/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element
        ? event.target.closest('[data-password-visibility-toggle]')
        : null;

    if (!(target instanceof HTMLButtonElement)) {
        return;
    }

    const field = target.closest('[data-password-visibility]');
    const input = field?.querySelector('input[type="password"], input[type="text"]');

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const isVisible = input.type === 'text';
    input.type = isVisible ? 'password' : 'text';

    const label = isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe';
    target.setAttribute('aria-label', label);
    target.setAttribute('title', label);
    target.setAttribute('aria-pressed', String(!isVisible));

    field.querySelector('[data-password-visibility-slash]')?.toggleAttribute('hidden', !isVisible);
});

// Le contrôle visuel doit rester disponible même si un contrôleur Stimulus
// est encore en cours de chargement. Les contrôleurs métier sont chargés juste
// après l'installation de ces interactions de base.
import('./stimulus_bootstrap.js').catch((error) => {
    console.error('Impossible de charger les interactions StickVerse.', error);
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.closest('[data-rewards-page-root]')) {
        return;
    }

    event.preventDefault();

    const scrollX = window.scrollX;
    const scrollY = window.scrollY;
    const submitButton = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton?.textContent;

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Récupération…';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const documentActualise = new DOMParser().parseFromString(
            await response.text(),
            'text/html',
        );
        const contenuActuel = document.querySelector('[data-rewards-page-root]');
        const nouveauContenu = documentActualise.querySelector('[data-rewards-page-root]');

        if (!response.ok || !contenuActuel || !nouveauContenu) {
            throw new Error('La réponse ne contient pas la page des récompenses.');
        }

        contenuActuel.replaceWith(nouveauContenu);

        const soldeActuel = document.querySelector('[data-solde-pieces]');
        const nouveauSolde = documentActualise.querySelector('[data-solde-pieces]');
        if (soldeActuel && nouveauSolde) {
            soldeActuel.replaceWith(nouveauSolde);
        }

        const lienActuel = document.querySelector('.site-rewards-link');
        const nouveauLien = documentActualise.querySelector('.site-rewards-link');
        if (lienActuel && nouveauLien) {
            lienActuel.replaceWith(nouveauLien);
        }

        window.history.replaceState(null, '', response.url);
        requestAnimationFrame(() => window.scrollTo(scrollX, scrollY));
    } catch (error) {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText ?? 'Réclamer';
        }

        HTMLFormElement.prototype.submit.call(form);
    }
});
