import { Controller } from '@hotwired/stimulus';

/**
 * Barre de progression affichée pendant les navigations et soumissions de
 * formulaire pilotées par Turbo (utile notamment pour les étapes du
 * formulaire d'inscription, qui envoient des pièces jointes et peuvent donc
 * prendre plusieurs secondes sur une connexion lente).
 *
 * L'élément est marqué `data-turbo-permanent` : il n'est ni recréé ni
 * déconnecté d'une navigation à l'autre, donc `connect()` ne s'exécute
 * qu'une seule fois et les écouteurs restent actifs pour toute la session.
 */
export default class extends Controller {
    static SHOW_DELAY_MS = 200;

    connect() {
        this.showTimeout = null;

        this.onLoadingStart = () => this.scheduleShow();
        this.onLoadingEnd = () => this.hide();

        document.addEventListener('turbo:before-visit', this.onLoadingStart);
        document.addEventListener('turbo:submit-start', this.onLoadingStart);
        document.addEventListener('turbo:load', this.onLoadingEnd);
        document.addEventListener('turbo:submit-end', this.onLoadingEnd);
        document.addEventListener('turbo:before-cache', this.onLoadingEnd);
        document.addEventListener('turbo:frame-load', this.onLoadingEnd);
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this.onLoadingStart);
        document.removeEventListener('turbo:submit-start', this.onLoadingStart);
        document.removeEventListener('turbo:load', this.onLoadingEnd);
        document.removeEventListener('turbo:submit-end', this.onLoadingEnd);
        document.removeEventListener('turbo:before-cache', this.onLoadingEnd);
        document.removeEventListener('turbo:frame-load', this.onLoadingEnd);
        clearTimeout(this.showTimeout);
    }

    scheduleShow() {
        // Petit délai pour éviter un flash de la barre sur les navigations
        // quasi instantanées (page déjà en cache, connexion rapide, etc.).
        clearTimeout(this.showTimeout);
        this.showTimeout = setTimeout(() => {
            this.element.hidden = false;
        }, this.constructor.SHOW_DELAY_MS);
    }

    hide() {
        clearTimeout(this.showTimeout);
        this.element.hidden = true;
    }
}
