import { Controller } from '@hotwired/stimulus';

/**
 * Constructeur d’équipe : l’interface manipule les quatre selects Symfony,
 * ce qui conserve la validation serveur et rend le formulaire progressif.
 */
export default class extends Controller {
    static targets = [
        'slot',
        'select',
        'inventoryCard',
        'inventoryPanel',
        'activeSlotLabel',
        'selectionStatus',
        'summaryPower',
        'message',
        'submit',
    ];

    static values = {
        limit: Number,
    };

    connect() {
        this.activeSlot = 'A';
        this.handleSelectChange = () => this.actualiser();

        this.selectTargets.forEach((select) => {
            select.addEventListener('change', this.handleSelectChange);
        });

        this.actualiser();
    }

    disconnect() {
        this.selectTargets.forEach((select) => {
            select.removeEventListener('change', this.handleSelectChange);
        });
    }

    activerEmplacement(event) {
        event.preventDefault();
        const slot = event.currentTarget.dataset.slot;

        if (!this.estUnEmplacement(slot)) {
            return;
        }

        this.activeSlot = slot;
        this.actualiser();
        this.inventoryPanelTarget.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    }

    selectionnerCarte(event) {
        event.preventDefault();
        const carte = event.currentTarget;
        const id = carte.dataset.stickmanId;
        const select = this.selectPourSlot(this.activeSlot);

        if (!(select instanceof HTMLSelectElement) || !id || carte.disabled) {
            return;
        }

        // Une carte déjà affectée à un autre emplacement reste désactivée.
        const emplacementExistant = this.emplacementPourCarte(id);
        if (emplacementExistant && emplacementExistant !== this.activeSlot) {
            return;
        }

        select.value = id;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        const emplacementSuivant = this.emplacements().find(
            (slot) => !this.selectPourSlot(slot)?.value,
        );
        if (emplacementSuivant) {
            this.activeSlot = emplacementSuivant;
            this.actualiser();
        }
    }

    retirerCarte(event) {
        event.preventDefault();
        const slot = event.currentTarget.dataset.slot;
        const select = this.selectPourSlot(slot);

        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        select.value = '';
        this.activeSlot = slot;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    actualiser() {
        const selections = this.selections();

        this.slotTargets.forEach((slotElement) => {
            this.afficherEmplacement(slotElement, selections);
        });
        this.afficherInventaire(selections);
        this.afficherResume(selections);

        const nombreSelectionne = [...selections.values()]
            .filter((selection) => Boolean(selection.card)).length;
        this.selectionStatusTarget.textContent =
            `${nombreSelectionne} / 4 cartes sélectionnées`;
        this.activeSlotLabelTarget.textContent =
            `Emplacement ${this.activeSlot} sélectionné — choisis une carte`;
    }

    afficherEmplacement(slotElement, selections) {
        const slot = slotElement.dataset.slot;
        const selection = selections.get(slot);
        const contenu = slotElement.querySelector('[data-team-composer-slot-card]');
        const bouton = slotElement.querySelector('.team-slot-select');
        const retrait = slotElement.querySelector('.team-slot-remove');

        if (!(contenu instanceof HTMLElement) || !(bouton instanceof HTMLButtonElement)) {
            return;
        }

        slotElement.classList.toggle('is-active', slot === this.activeSlot);
        slotElement.classList.toggle('is-filled', Boolean(selection?.card));
        bouton.setAttribute(
            'aria-pressed',
            slot === this.activeSlot ? 'true' : 'false',
        );
        contenu.replaceChildren();

        if (selection?.card) {
            const carte = selection.card;
            const image = document.createElement('img');
            image.src = carte.dataset.stickmanImage ?? '';
            image.alt = '';

            const details = document.createElement('span');
            details.className = 'team-slot-details';

            const nom = document.createElement('strong');
            nom.textContent = carte.dataset.stickmanName ?? 'Stickman';
            const statistiques = document.createElement('small');
            statistiques.textContent = `Puissance ${carte.dataset.stickmanPuissance ?? 0} · ATQ ${carte.dataset.stickmanAttaque ?? 0} · DÉF ${carte.dataset.stickmanDefense ?? 0}`;
            details.append(nom, statistiques);

            const passifs = document.createElement('span');
            passifs.className = 'team-slot-passifs';
            const listePassifs = this.lirePassifs(carte);
            if (listePassifs.length === 0) {
                const aucun = document.createElement('small');
                aucun.textContent = 'Aucun passif';
                passifs.append(aucun);
            } else {
                listePassifs.slice(0, 6).forEach((passif) => {
                    const badge = document.createElement('span');
                    badge.className = 'team-slot-passif';
                    badge.textContent = String(passif.nom ?? 'Passif');
                    badge.title = [passif.nom, passif.description].filter(Boolean).join(' — ');
                    passifs.append(badge);
                });
            }
            details.append(passifs);
            contenu.append(image, details);

            if (retrait instanceof HTMLButtonElement) {
                retrait.hidden = false;
            }
        } else {
            const vide = document.createElement('span');
            vide.className = 'team-slot-empty';
            vide.textContent = 'Choisir une carte';
            contenu.append(vide);

            if (retrait instanceof HTMLButtonElement) {
                retrait.hidden = true;
            }
        }
    }

    afficherInventaire(selections) {
        this.inventoryCardTargets.forEach((carte) => {
            const id = carte.dataset.stickmanId ?? '';
            const emplacement = this.emplacementPourCarte(id);
            const estDejaSelectionnee = Boolean(emplacement);
            const etat = carte.querySelector('[data-team-composer-card-state]');

            carte.classList.toggle('is-selected', estDejaSelectionnee);
            carte.classList.toggle('is-current', emplacement === this.activeSlot);
            // Une carte sélectionnée est toujours verrouillée, même dans
            // l’emplacement actif. Il faut la retirer avant de la remplacer,
            // ce qui évite toute ambiguïté visuelle sur les doublons.
            carte.disabled = estDejaSelectionnee;

            if (etat instanceof HTMLElement) {
                etat.textContent = emplacement
                        ? `Déjà dans l’emplacement ${emplacement}`
                        : 'Sélectionner';
            }
        });

        this.inventoryPanelTarget.dataset.activeSlot = this.activeSlot;
    }

    afficherResume(selections) {
        const groupes = {
            X: this.statistiquesVides(),
            Y: this.statistiquesVides(),
        };
        let nombreValide = 0;

        selections.forEach((selection) => {
            if (!selection.card) {
                return;
            }

            nombreValide += 1;
            const groupe = selection.groupe;
            const statistiques = groupes[groupe];
            statistiques.puissance += this.nombre(carteValeur(selection.card, 'stickmanPuissance'));
            statistiques.pv += this.nombre(carteValeur(selection.card, 'stickmanPv'));
            statistiques.attaque += this.nombre(carteValeur(selection.card, 'stickmanAttaque'));
            statistiques.defense += this.nombre(carteValeur(selection.card, 'stickmanDefense'));
        });

        const total = this.statistiquesVides();
        ['X', 'Y'].forEach((groupe) => {
            Object.keys(total).forEach((cle) => {
                total[cle] += groupes[groupe][cle];
            });
            this.texteResume(`${groupe}-attaque`, groupes[groupe].attaque);
            this.texteResume(`${groupe}-defense`, groupes[groupe].defense);
            this.texteResume(`${groupe}-pv`, groupes[groupe].pv);
            this.texteResume(`${groupe}-puissance`, groupes[groupe].puissance);
        });

        this.summaryPowerTarget.textContent = String(total.puissance);
        const nombreUnique = new Set(
            [...selections.values()]
                .map((selection) => selection.id)
                .filter(Boolean),
        ).size;
        const equipeComplete = nombreValide === 4 && nombreUnique === 4;
        const depasseLimite = equipeComplete && total.puissance > this.limitValue;

        this.submitTarget.disabled = !equipeComplete || depasseLimite;
        this.submitTarget.setAttribute('aria-disabled', String(this.submitTarget.disabled));

        if (!equipeComplete) {
            if (nombreValide < 4) {
                const restant = 4 - nombreValide;
                this.messageTarget.textContent =
                    `Choisis encore ${restant} emplacement${restant > 1 ? 's' : ''} pour enregistrer ton équipe.`;
            } else {
                this.messageTarget.textContent =
                    'Chaque emplacement doit contenir une carte différente.';
            }
            this.messageTarget.className = 'team-composer-message';
        } else if (depasseLimite) {
            this.messageTarget.textContent =
                `Cette composition dépasse la limite de ${this.limitValue} puissance.`;
            this.messageTarget.className = 'team-composer-message team-composer-message--error';
        } else {
            this.messageTarget.textContent =
                `Composition complète — ${total.puissance} / ${this.limitValue} puissance.`;
            this.messageTarget.className = 'team-composer-message team-composer-message--success';
        }
    }

    selections() {
        const selections = new Map();

        this.emplacements().forEach((slot) => {
            const select = this.selectPourSlot(slot);
            const id = select?.value ?? '';
            const card = id
                ? this.inventoryCardTargets.find(
                    (element) => element.dataset.stickmanId === id,
                ) ?? null
                : null;
            selections.set(slot, {
                id,
                card,
                groupe: ['A', 'B'].includes(slot) ? 'X' : 'Y',
            });
        });

        return selections;
    }

    emplacementPourCarte(id) {
        if (!id) {
            return null;
        }

        for (const slot of this.emplacements()) {
            if (this.selectPourSlot(slot)?.value === id) {
                return slot;
            }
        }

        return null;
    }

    selectPourSlot(slot) {
        return this.selectTargets.find(
            (select) => select.dataset.slot === slot,
        ) ?? null;
    }

    emplacements() {
        return ['A', 'B', 'C', 'D'];
    }

    estUnEmplacement(slot) {
        return this.emplacements().includes(slot);
    }

    statistiquesVides() {
        return {
            puissance: 0,
            pv: 0,
            attaque: 0,
            defense: 0,
        };
    }

    texteResume(cle, valeur) {
        const element = this.element.querySelector(`[data-summary="${cle}"]`);
        if (element) {
            element.textContent = String(valeur);
        }
    }

    nombre(valeur) {
        const nombre = Number.parseInt(String(valeur ?? 0), 10);

        return Number.isFinite(nombre) ? nombre : 0;
    }

    lirePassifs(carte) {
        try {
            const passifs = JSON.parse(carte.dataset.stickmanPassifs ?? '[]');

            return Array.isArray(passifs) ? passifs.filter((passif) => passif && typeof passif === 'object') : [];
        } catch (error) {
            return [];
        }
    }
}

function carteValeur(carte, cle) {
    return carte.dataset[cle] ?? 0;
}
