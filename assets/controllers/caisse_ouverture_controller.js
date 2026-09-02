import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'form',
        'pageError',
        'overlay',
        'title',
        'status',
        'loadingPhase',
        'roulettePhase',
        'viewport',
        'track',
        'reveal',
        'particles',
        'acquisitionStatus',
        'resultCard',
        'rarity',
        'image',
        'name',
        'role',
        'power',
        'hp',
        'attack',
        'defense',
        'passives',
        'collectionProgress',
        'openAgainButton',
        'inventoryLink',
    ];

    connect() {
        this.processing = false;
        this.activeForm = null;
        this.activeButton = null;
        this.originalButtonText = '';
        this.animationGeneration = 0;
        this.activeCanOpenAgain = true;
        this.lastFocusedElement = null;
        this.requestController = null;
        this.handleKeydown = (event) => {
            if (event.key === 'Escape' && !this.overlayTarget.hidden) {
                this.fermer();
            }
        };
        document.addEventListener('keydown', this.handleKeydown);
    }

    disconnect() {
        document.removeEventListener('keydown', this.handleKeydown);
        document.body.classList.remove('crate-opening-active');
    }

    async ouvrir(event) {
        event.preventDefault();

        if (this.processing) {
            return;
        }

        const form = event.currentTarget;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        this.processing = true;
        this.activeForm = form;
        this.activeButton = event.submitter instanceof HTMLButtonElement
            ? event.submitter
            : form.querySelector('[data-opening-submit]');
        this.originalButtonText = this.activeButton?.textContent ?? 'Ouvrir la caisse';
        this.lastFocusedElement = document.activeElement;
        this.activeCanOpenAgain = true;
        this.masquerErreurPage();
        this.preparerOverlay();
        const ouvertureGeneration = this.animationGeneration;

        if (this.activeButton) {
            this.activeButton.disabled = true;
            this.activeButton.textContent = 'Tirage sécurisé…';
        }

        const requestController = new AbortController();
        this.requestController = requestController;
        const delaiServeur = window.setTimeout(() => requestController.abort(), 15000);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                signal: requestController.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => null);

            if (!response.ok || !payload?.ok) {
                throw new Error(payload?.error ?? 'L’ouverture a échoué. Réessaie dans un instant.');
            }

            this.mettreAJourPage(payload);
            this.mettreAJourJeton(payload.nextOpeningToken);
            this.activeCanOpenAgain = Boolean(payload.canOpenAgain);
            if (ouvertureGeneration !== this.animationGeneration) {
                return;
            }
            const animationTerminee = await this.lancerRoulette(payload, ouvertureGeneration);
            if (!animationTerminee) {
                return;
            }
        } catch (error) {
            if (ouvertureGeneration !== this.animationGeneration) {
                return;
            }

            const message = error instanceof DOMException && error.name === 'AbortError'
                ? 'Le serveur met trop de temps à répondre. Ferme cette fenêtre puis réessaie : la même ouverture sera reprise sans consommer une seconde caisse.'
                : error instanceof Error
                    ? error.message
                    : 'L’ouverture a échoué. Réessaie dans un instant.';
            this.afficherErreur(message);
            this.processing = false;
            this.restaurerBouton();
        } finally {
            window.clearTimeout(delaiServeur);
            if (this.requestController === requestController) {
                this.requestController = null;
            }
        }
    }

    preparerOverlay() {
        this.animationGeneration += 1;
        this.overlayTarget.hidden = false;
        this.overlayTarget.setAttribute('aria-hidden', 'false');
        this.overlayTarget.scrollTop = 0;
        if (this.hasLoadingPhaseTarget) {
            this.loadingPhaseTarget.hidden = false;
            this.roulettePhaseTarget.hidden = true;
        } else {
            // Compatibilité avec une page déjà ouverte avant le déploiement du
            // bloc de chargement : ne jamais bloquer la requête serveur.
            this.roulettePhaseTarget.hidden = false;
        }
        this.revealTarget.hidden = true;
        this.trackTarget.replaceChildren();
        this.trackTarget.style.transition = 'none';
        this.trackTarget.style.transform = 'translate3d(0, 0, 0)';
        this.titleTarget.textContent = 'Préparation de la caisse…';
        this.statusTarget.textContent = 'Préparation du rouleau…';
        this.overlayTarget.className = 'crate-opening-overlay';
        document.body.classList.add('crate-opening-active');
        this.overlayTarget.querySelector('.crate-opening-close')?.focus();
    }

    async lancerRoulette(payload, generation) {
        const contenu = Array.isArray(payload.roulette) ? payload.roulette : [];
        const gain = payload.reward;

        if (contenu.length === 0 || !gain) {
            throw new Error('La caisse ne contient aucune carte affichable.');
        }

        const nombreCartes = Math.max(40, contenu.length * 3);
        const indexGagnant = nombreCartes - 6;
        const decalage = Number(payload.openingId ?? 0) % contenu.length;

        for (let index = 0; index < nombreCartes; index += 1) {
            const stickman = index === indexGagnant
                ? gain
                : contenu[(index + decalage) % contenu.length];
            this.trackTarget.append(this.creerCarteRoulette(stickman, index === indexGagnant));
        }

        this.titleTarget.textContent = payload.crate?.name ?? 'Ouverture de caisse';
        this.statusTarget.textContent = 'Le rouleau ralentit…';
        this.overlayTarget.classList.add(`crate-opening-overlay--r${gain.rarity}`);
        if (this.hasLoadingPhaseTarget) {
            this.loadingPhaseTarget.hidden = true;
        }
        this.roulettePhaseTarget.hidden = false;

        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

        const carte = this.trackTarget.querySelector('[data-winning-card="true"]');
        if (!(carte instanceof HTMLElement)) {
            throw new Error('La carte gagnante ne peut pas être positionnée.');
        }

        const viewportWidth = this.viewportTarget.getBoundingClientRect().width;
        const carteWidth = carte.getBoundingClientRect().width;
        const styleTrack = window.getComputedStyle(this.trackTarget);
        const gap = Number.parseFloat(styleTrack.columnGap || styleTrack.gap || '0');
        const centreGagnant = indexGagnant * (carteWidth + gap) + carteWidth / 2;
        const positionFinale = viewportWidth / 2 - centreGagnant;
        const positionInitiale = viewportWidth * 0.82;
        const duree = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 900
            : 4300 + Math.min(5, Number(gain.rarity ?? 1)) * 160;

        this.trackTarget.style.transform = `translate3d(${positionInitiale}px, 0, 0)`;
        this.trackTarget.getBoundingClientRect();
        this.trackTarget.style.transition = `transform ${duree}ms cubic-bezier(0.08, 0.68, 0.08, 1)`;
        this.trackTarget.style.transform = `translate3d(${positionFinale}px, 0, 0)`;

        await this.attendreTransition(this.trackTarget, duree + 250);
        if (generation !== this.animationGeneration) {
            return false;
        }

        this.trackTarget.querySelectorAll('.crate-roulette-card').forEach((element) => {
            element.classList.toggle('crate-roulette-card--dimmed', element !== carte);
        });
        carte.classList.add('crate-roulette-card--winner');
        this.statusTarget.textContent = `${gain.name} est remporté !`;

        await new Promise((resolve) => window.setTimeout(resolve, 650));
        if (generation !== this.animationGeneration) {
            return false;
        }

        this.afficherRevelation(payload);
        this.processing = false;
        this.restaurerBouton(Boolean(payload.canOpenAgain));

        return true;
    }

    creerCarteRoulette(stickman, gagnante) {
        const carte = document.createElement('article');
        carte.className = `crate-roulette-card crate-rarity-${stickman.rarity}`;
        carte.dataset.winningCard = gagnante ? 'true' : 'false';

        const rarete = document.createElement('span');
        rarete.className = 'crate-roulette-rarity';
        rarete.textContent = `R${stickman.rarity}`;

        const image = document.createElement('img');
        image.src = stickman.image;
        image.alt = '';
        image.loading = 'eager';
        image.decoding = 'async';

        const nom = document.createElement('strong');
        nom.textContent = stickman.name;

        carte.append(rarete, image, nom);

        return carte;
    }

    afficherRevelation(payload) {
        const gain = payload.reward;
        const collection = payload.collection;

        this.roulettePhaseTarget.hidden = true;
        this.revealTarget.hidden = false;
        this.resultCardTarget.className = `crate-reveal-card crate-rarity-${gain.rarity}`;
        this.acquisitionStatusTarget.textContent = gain.isNew
            ? 'NOUVEAU !'
            : `POSSÉDÉ x${gain.quantity}`;
        this.rarityTarget.textContent = `RARETÉ R${gain.rarity}`;
        this.imageTarget.src = gain.image;
        this.imageTarget.alt = gain.name;
        this.nameTarget.textContent = gain.name;
        this.roleTarget.textContent = gain.role || 'Non défini';
        this.powerTarget.textContent = String(gain.power);
        this.hpTarget.textContent = String(gain.hp);
        this.attackTarget.textContent = String(gain.attack);
        this.defenseTarget.textContent = String(gain.defense);
        this.afficherPassifs(gain.passives);

        this.collectionProgressTarget.textContent = collection.complete
            ? `COLLECTION COMPLÈTE — ${collection.name} — ${collection.owned} / ${collection.total}`
            : `${collection.name.toUpperCase()} — ${collection.owned} / ${collection.total}`;
        this.collectionProgressTarget.classList.toggle(
            'crate-collection-progress--complete',
            Boolean(collection.complete),
        );
        this.openAgainButtonTarget.hidden = !payload.canOpenAgain;
        this.openAgainButtonTarget.disabled = !payload.canOpenAgain;
        this.inventoryLinkTarget.href = payload.inventoryUrl;
        this.creerParticules(Number(gain.rarity));
        this.revealTarget.classList.remove('crate-reveal--visible');
        requestAnimationFrame(() => this.revealTarget.classList.add('crate-reveal--visible'));
        if (payload.canOpenAgain) {
            this.openAgainButtonTarget.focus();
        } else {
            this.inventoryLinkTarget.focus();
        }
    }

    afficherPassifs(passifs) {
        this.passivesTarget.replaceChildren();
        const liste = Array.isArray(passifs) ? passifs : [];

        if (liste.length === 0) {
            const vide = document.createElement('p');
            vide.textContent = 'Aucun passif pour le moment.';
            this.passivesTarget.append(vide);
            return;
        }

        const elementListe = document.createElement('ul');
        liste.forEach((passif) => {
            const item = document.createElement('li');
            item.textContent = typeof passif === 'string'
                ? passif
                : passif?.description ?? 'Passif';
            elementListe.append(item);
        });
        this.passivesTarget.append(elementListe);
    }

    creerParticules(rarete) {
        this.particlesTarget.replaceChildren();
        if (rarete < 4) {
            return;
        }

        const nombre = rarete === 5 ? 26 : 14;
        for (let index = 0; index < nombre; index += 1) {
            const particule = document.createElement('span');
            particule.style.setProperty('--particle-x', `${(index * 37) % 100}%`);
            particule.style.setProperty('--particle-delay', `${(index % 7) * 70}ms`);
            particule.style.setProperty('--particle-size', `${4 + (index % 4) * 2}px`);
            this.particlesTarget.append(particule);
        }
    }

    mettreAJourJeton(jeton) {
        const champ = this.activeForm?.querySelector('input[name="_ouverture"]');
        if (champ instanceof HTMLInputElement && typeof jeton === 'string') {
            champ.value = jeton;
        }
    }

    mettreAJourPage(payload) {
        document.querySelectorAll('[data-solde-pieces]').forEach((element) => {
            element.textContent = `${payload.wallet.pieces} pièces`;
        });
        document.querySelectorAll('[data-solde-pieces-page] strong').forEach((element) => {
            element.textContent = `${payload.wallet.pieces} pièces`;
        });
        document.querySelectorAll('[data-caisses-premiers-renforts-page] strong').forEach((element) => {
            element.textContent = String(payload.wallet.freeCrates);
        });
    }

    afficherErreur(message) {
        this.titleTarget.textContent = 'Ouverture impossible';
        this.statusTarget.textContent = message;
        if (this.hasLoadingPhaseTarget) {
            this.loadingPhaseTarget.hidden = true;
        }
        this.roulettePhaseTarget.hidden = true;
        this.overlayTarget.scrollTop = 0;
        if (this.hasPageErrorTarget) {
            this.pageErrorTarget.textContent = message;
            this.pageErrorTarget.hidden = false;
        }
    }

    masquerErreurPage() {
        if (this.hasPageErrorTarget) {
            this.pageErrorTarget.hidden = true;
            this.pageErrorTarget.textContent = '';
        }
    }

    ouvrirEncore() {
        if (!this.activeForm || this.openAgainButtonTarget.disabled) {
            return;
        }

        const form = this.activeForm;
        this.fermer();
        requestAnimationFrame(() => form.requestSubmit());
    }

    fermer() {
        this.animationGeneration += 1;
        this.requestController?.abort();
        this.requestController = null;
        this.overlayTarget.hidden = true;
        this.overlayTarget.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('crate-opening-active');
        this.processing = false;
        this.restaurerBouton(this.activeCanOpenAgain);

        if (this.lastFocusedElement instanceof HTMLElement) {
            this.lastFocusedElement.focus();
        }
    }

    restaurerBouton(peutOuvrirEncore = true) {
        if (!this.activeButton) {
            return;
        }

        this.activeButton.textContent = this.originalButtonText;
        this.activeButton.disabled = !peutOuvrirEncore;
    }

    attendreTransition(element, delaiMaximum) {
        return new Promise((resolve) => {
            let termine = false;
            const terminer = () => {
                if (termine) {
                    return;
                }
                termine = true;
                element.removeEventListener('transitionend', terminer);
                resolve();
            };

            element.addEventListener('transitionend', terminer, { once: true });
            window.setTimeout(terminer, delaiMaximum);
        });
    }
}
