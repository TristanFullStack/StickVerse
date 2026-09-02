import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['card', 'count', 'total', 'inputs', 'submit', 'cart', 'basket'];

    connect() {
        this.selection = new Map();
        this.totalPieces = 0;
        this.totalCartes = 0;
        this.handleBasketClick = (event) => {
            const bouton = event.target instanceof Element
                ? event.target.closest('[data-inventory-sale-remove]')
                : null;
            if (!(bouton instanceof HTMLElement)) return;
            event.preventDefault();
            event.stopPropagation();
            const id = bouton.dataset.inventorySaleRemove;
            const quantite = this.selection.get(id) || 0;
            if (quantite <= 1) this.selection.delete(id);
            else this.selection.set(id, quantite - 1);
            this.actualiser();
        };
        if (this.hasBasketTarget) this.basketTarget.addEventListener('click', this.handleBasketClick);
        this.actualiser();
    }

    disconnect() {
        if (this.hasBasketTarget) this.basketTarget.removeEventListener('click', this.handleBasketClick);
    }

    toggle(event) {
        const carte = event.currentTarget;
        const id = carte.dataset.inventoryId;
        const maximum = Math.max(
            0,
            Number(carte.dataset.maxQuantity || 0) - Number(carte.dataset.protectedQuantity || 0),
        );
        if (!id || maximum < 1) return;

        const actuel = this.selection.get(id) || 0;
        if (actuel >= maximum) return;

        this.selection.set(id, actuel + 1);
        this.animerVersPanier(carte);
        this.actualiser();
    }

    actualiser() {
        this.totalPieces = 0;
        this.totalCartes = 0;
        this.inputsTarget.replaceChildren();

        this.cardTargets.forEach((carte) => {
            const id = carte.dataset.inventoryId;
            const quantite = this.selection.get(id) || 0;
            const prix = Number(carte.dataset.unitPrice || 0);
            const maximum = Math.max(
                0,
                Number(carte.dataset.maxQuantity || 0) - Number(carte.dataset.protectedQuantity || 0),
            );
            this.totalPieces += quantite * prix;
            this.totalCartes += quantite;
            carte.classList.toggle('is-in-cart', quantite > 0);
            carte.classList.toggle('is-sold-out-selection', quantite >= maximum);
            const indication = carte.querySelector('[data-inventory-sale-quantity]');
            if (indication) indication.textContent = quantite > 0 ? `${quantite} vendu${quantite > 1 ? 's' : ''}` : '0 vendu';
            if (quantite > 0) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `ventes[${id}]`;
                input.value = String(quantite);
                this.inputsTarget.append(input);
            }
        });

        this.totalTarget.textContent = String(this.totalPieces);
        this.countTarget.textContent = `${this.totalCartes} carte${this.totalCartes > 1 ? 's' : ''}`;
        this.submitTarget.disabled = this.totalCartes === 0;
        this.actualiserPanier();
    }

    actualiserPanier() {
        if (!this.hasBasketTarget) return;
        this.basketTarget.replaceChildren();

        this.selection.forEach((quantite, id) => {
            const source = this.cardTargets.find((carte) => carte.dataset.inventoryId === id);
            if (!source) return;

            const element = document.createElement('div');
            element.className = 'inventory-sale-basket-card';

            const imageSource = source.querySelector('img');
            if (imageSource instanceof HTMLImageElement) {
                const image = document.createElement('img');
                image.src = imageSource.currentSrc || imageSource.src;
                image.alt = '';
                element.append(image);
            }

            const nom = document.createElement('strong');
            nom.textContent = source.querySelector('h3')?.textContent?.trim() || 'Carte';
            element.append(nom);

            const compteur = document.createElement('span');
            compteur.textContent = `×${quantite}`;
            element.append(compteur);

            const retirer = document.createElement('button');
            retirer.type = 'button';
            retirer.dataset.inventorySaleRemove = id;
            retirer.setAttribute('aria-label', `Retirer un exemplaire de ${nom.textContent}`);
            retirer.textContent = '×';
            element.append(retirer);
            this.basketTarget.append(element);
        });
    }

    animerVersPanier(carte) {
        if (!this.hasCartTarget) return;
        const origine = carte.getBoundingClientRect();
        const destination = this.cartTarget.getBoundingClientRect();
        const clone = carte.cloneNode(true);
        clone.classList.add('inventory-sale-flying-card');
        clone.style.width = `${Math.min(180, origine.width)}px`;
        clone.style.left = `${origine.left}px`;
        clone.style.top = `${origine.top}px`;
        document.body.append(clone);
        requestAnimationFrame(() => {
            clone.style.transform = `translate3d(${destination.left - origine.left + destination.width / 2 - origine.width / 2}px, ${destination.top - origine.top}px, 0) scale(.28)`;
            clone.style.opacity = '0';
        });
        window.setTimeout(() => clone.remove(), 480);
    }

    submit(event) {
        if (this.totalCartes === 0) event.preventDefault();
    }
}
