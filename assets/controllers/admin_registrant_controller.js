import { Controller } from '@hotwired/stimulus';

/**
 * Fiche détail admin : ouverture de la modale MD3 de confirmation de suppression
 * et envoi de la requête DELETE correspondante.
 */
export default class extends Controller {
    static targets = ['dialog'];
    static values = {
        deleteUrl: String,
        csrfToken: String,
        redirectUrl: String,
    };

    openDeleteDialog() {
        this.dialogTarget.show();
    }

    closeDeleteDialog() {
        this.dialogTarget.close();
    }

    async confirmDelete(event) {
        const button = event.currentTarget;
        button.disabled = true;

        try {
            const response = await fetch(this.deleteUrlValue, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ _token: this.csrfTokenValue }),
            });

            if (response.ok || response.redirected) {
                window.location.href = this.redirectUrlValue;
                return;
            }

            throw new Error(`HTTP ${response.status}`);
        } catch (error) {
            button.disabled = false;
            this.dialogTarget.close();
            // eslint-disable-next-line no-alert
            alert("La suppression a échoué. Merci de réessayer.");
        }
    }
}
