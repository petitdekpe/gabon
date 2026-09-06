import { Controller } from '@hotwired/stimulus';

/** Ferme un message flash au clic sur son bouton de fermeture. */
export default class extends Controller {
    close() {
        this.element.remove();
    }
}
