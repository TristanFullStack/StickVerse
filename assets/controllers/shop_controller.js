import { Controller } from '@hotwired/stimulus';

/**
 * Keeps the crate purchase UI honest before the request reaches Symfony.
 * The server still validates the balance and quantity authoritatively.
 */
export default class extends Controller {
    static targets = ['form', 'quantity', 'total', 'submit'];

    connect() {
        this.balance = Number(this.element.dataset.shopBalanceValue || 0);
        this.quantityTargets.forEach((quantity) => this.actualiser(quantity));
    }

    update(event) {
        const quantity = event?.currentTarget instanceof HTMLInputElement
            ? event.currentTarget
            : this.quantityTargets[0];
        if (quantity) this.actualiser(quantity);
    }

    actualiser(quantity) {
        const form = quantity.closest('form');
        if (!(form instanceof HTMLFormElement)) return;

        const total = this.totalTargets.find((target) => form.contains(target));
        const submit = this.submitTargets.find((target) => form.contains(target));
        const unite = Math.max(0, Number(quantity.dataset.shopUnitPrice || 0));
        const maximum = Math.min(100, Number(quantity.max || 100));
        let quantite = Number.parseInt(quantity.value, 10);
        if (!Number.isFinite(quantite)) quantite = 1;
        quantite = Math.max(1, Math.min(maximum, quantite));
        quantity.value = String(quantite);
        const montant = unite * quantite;

        if (total) total.textContent = String(montant);
        if (!submit) return;

        const valide = montant <= this.balance;
        submit.disabled = !valide;
        submit.textContent = valide
            ? (submit.dataset.shopEnabledLabel || 'Acheter')
            : 'Solde insuffisant';
    }

    confirm(event) {
        const form = event.currentTarget;
        if (!(form instanceof HTMLFormElement)) return;

        const quantity = this.quantityTargets.find((target) => form.contains(target));
        if (!quantity) return;
        this.actualiser(quantity);

        const unite = Math.max(0, Number(quantity.dataset.shopUnitPrice || 0));
        const quantite = Number.parseInt(quantity.value, 10);
        const montant = unite * quantite;
        if (!Number.isFinite(quantite) || quantite < 1 || montant > this.balance) {
            event.preventDefault();
            return;
        }

        if (!window.confirm(`Acheter ${quantite} caisse${quantite > 1 ? 's' : ''} pour ${montant} pièces ?`)) {
            event.preventDefault();
            return;
        }

        const submit = this.submitTargets.find((target) => form.contains(target));
        if (submit) submit.disabled = true;
    }
}
