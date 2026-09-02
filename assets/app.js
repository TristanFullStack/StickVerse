import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

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
