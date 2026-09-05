import { Controller } from '@hotwired/stimulus';

/**
 * Toggles a password field between its protected and visible states.
 * The server still receives the same value; this only changes the local display.
 */
export default class extends Controller {
    static targets = ['button', 'showIcon', 'hideIcon'];

    connect() {
        this.input = this.element.querySelector('input[type="password"], input[type="text"]');

        if (!(this.input instanceof HTMLInputElement)) {
            this.input = null;
            return;
        }

        this.updateButton();
    }

    toggle() {
        if (!this.input) {
            return;
        }

        this.input.type = this.input.type === 'password' ? 'text' : 'password';
        this.updateButton();
    }

    updateButton() {
        if (!this.input || !this.hasButtonTarget) {
            return;
        }

        const isVisible = this.input.type === 'text';
        const label = isVisible ? 'Masquer le mot de passe' : 'Afficher le mot de passe';

        this.buttonTarget.setAttribute('aria-label', label);
        this.buttonTarget.setAttribute('title', label);
        this.buttonTarget.setAttribute('aria-pressed', String(isVisible));

        if (this.hasShowIconTarget) {
            this.showIconTarget.hidden = isVisible;
        }

        if (this.hasHideIconTarget) {
            this.hideIconTarget.hidden = !isVisible;
        }
    }
}
