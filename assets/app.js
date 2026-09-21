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

/*
 * Lecture tactile des passifs.
 *
 * Les badges de passif vivent parfois dans un lien ou dans un bouton de
 * sélection de carte. La capture permet donc d'intercepter l'appui avant que
 * la carte parente ne soit ouverte ou sélectionnée. Une seule infobulle est
 * conservée à la fois et sa description peut défiler sur petit écran.
 */
const passifViewer = {
    trigger: null,
    popover: null,
    popoverId: 0,
};

const passifViewerSelector = '[data-passif-viewer-trigger]';

function passifViewerTrigger(event) {
    return event.target instanceof Element
        ? event.target.closest(passifViewerSelector)
        : null;
}

function fermerPassifViewer() {
    if (passifViewer.trigger instanceof HTMLElement) {
        passifViewer.trigger.setAttribute('aria-expanded', 'false');
    }

    passifViewer.popover?.remove();
    passifViewer.trigger = null;
    passifViewer.popover = null;
}

function positionnerPassifViewer() {
    const trigger = passifViewer.trigger;
    const popover = passifViewer.popover;

    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
        return;
    }

    const marge = 10;
    const declencheur = trigger.getBoundingClientRect();
    const dimensions = popover.getBoundingClientRect();
    const largeurViewport = document.documentElement.clientWidth;
    const hauteurViewport = window.innerHeight;
    const gaucheMaximale = Math.max(
        marge,
        largeurViewport - dimensions.width - marge,
    );
    const gaucheCentre = declencheur.left
        + ((declencheur.width - dimensions.width) / 2);
    const gauche = Math.min(
        gaucheMaximale,
        Math.max(marge, gaucheCentre),
    );
    const placeDessus = declencheur.top - dimensions.height - marge;
    const placeDessous = declencheur.bottom + marge;
    const haut = placeDessus >= marge || placeDessous + dimensions.height > hauteurViewport - marge
        ? Math.max(marge, placeDessus)
        : placeDessous;

    popover.style.left = `${gauche}px`;
    popover.style.top = `${Math.min(haut, Math.max(marge, hauteurViewport - dimensions.height - marge))}px`;
}

function ouvrirPassifViewer(trigger) {
    const nom = String(trigger.dataset.passifName ?? 'Passif').trim() || 'Passif';
    const description = String(trigger.dataset.passifDescription ?? '').trim();
    const popover = document.createElement('aside');
    const titre = document.createElement('strong');
    const texte = document.createElement('p');
    const aide = document.createElement('small');

    fermerPassifViewer();

    passifViewer.popoverId += 1;
    popover.id = `passif-viewer-${passifViewer.popoverId}`;
    popover.className = 'passif-viewer-popover';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', `Description du passif ${nom}`);
    popover.tabIndex = 0;

    titre.textContent = nom;
    texte.textContent = description || 'Aucune description disponible.';
    aide.textContent = 'Appuie ici pour fermer';
    popover.append(titre, texte, aide);
    document.body.append(popover);

    passifViewer.trigger = trigger;
    passifViewer.popover = popover;
    trigger.setAttribute('aria-expanded', 'true');
    trigger.setAttribute('aria-controls', popover.id);

    requestAnimationFrame(positionnerPassifViewer);
    popover.focus({ preventScroll: true });
}

function basculerPassifViewer(trigger) {
    if (passifViewer.trigger === trigger) {
        fermerPassifViewer();

        return;
    }

    ouvrirPassifViewer(trigger);
}

document.addEventListener('click', (event) => {
    const cible = event.target instanceof Element ? event.target : null;

    if (passifViewer.popover instanceof HTMLElement && cible
        && passifViewer.popover.contains(cible)) {
        event.preventDefault();
        event.stopPropagation();
        fermerPassifViewer();

        return;
    }

    const trigger = passifViewerTrigger(event);
    if (!(trigger instanceof HTMLElement)) {
        if (passifViewer.popover) {
            fermerPassifViewer();
        }

        return;
    }

    event.preventDefault();
    event.stopPropagation();
    basculerPassifViewer(trigger);
}, true);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && passifViewer.popover) {
        event.preventDefault();
        event.stopPropagation();
        fermerPassifViewer();

        return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    const trigger = passifViewerTrigger(event);
    if (!(trigger instanceof HTMLElement)) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    basculerPassifViewer(trigger);
}, true);

window.addEventListener('resize', positionnerPassifViewer);
window.addEventListener('scroll', positionnerPassifViewer, true);

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
