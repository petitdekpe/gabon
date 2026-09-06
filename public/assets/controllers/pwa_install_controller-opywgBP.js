import { Controller } from '@hotwired/stimulus';

/**
 * Installation de la webapp sur l'écran d'accueil (PWA).
 * - Android/Chrome/Edge : capture "beforeinstallprompt" et déclenche l'invite native.
 * - iOS/Safari : ne supporte pas cette API, on affiche les instructions manuelles.
 * - Déjà installée (mode standalone) : masque toute la section.
 */
export default class extends Controller {
    static targets = ['installButton', 'iosInstructions', 'alreadyInstalled'];

    connect() {
        this.deferredPrompt = null;

        if (this.isStandalone()) {
            this.showOnly(this.hasAlreadyInstalledTarget ? this.alreadyInstalledTarget : null);
            return;
        }

        if (this.isIos()) {
            this.showOnly(this.hasIosInstructionsTarget ? this.iosInstructionsTarget : null);
            return;
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredPrompt = event;
            if (this.hasInstallButtonTarget) {
                this.installButtonTarget.hidden = false;
            }
        });

        window.addEventListener('appinstalled', () => {
            this.showOnly(this.hasAlreadyInstalledTarget ? this.alreadyInstalledTarget : null);
        });

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // L'installation reste possible sans SW sur certains navigateurs ;
                // un échec d'enregistrement ne doit pas casser la page.
            });
        }
    }

    async install() {
        if (!this.deferredPrompt) {
            return;
        }

        this.deferredPrompt.prompt();
        await this.deferredPrompt.userChoice;
        this.deferredPrompt = null;
        this.installButtonTarget.hidden = true;
    }

    showOnly(visibleTarget) {
        [this.hasInstallButtonTarget && this.installButtonTarget, this.hasIosInstructionsTarget && this.iosInstructionsTarget, this.hasAlreadyInstalledTarget && this.alreadyInstalledTarget]
            .filter(Boolean)
            .forEach((el) => {
                el.hidden = el !== visibleTarget;
            });
    }

    isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }
}
