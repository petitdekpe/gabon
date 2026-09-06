import { Controller } from '@hotwired/stimulus';

/**
 * Diaporama du héros de la page d'accueil : avance automatiquement entre les
 * diapositives, avec une navigation manuelle par points.
 */
export default class extends Controller {
    static targets = ['slide', 'dot'];
    static values = { interval: { type: Number, default: 6000 } };

    connect() {
        this.index = 0;
        this.timer = null;

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.start();
        }
    }

    disconnect() {
        this.stop();
    }

    start() {
        this.stop();
        this.timer = window.setInterval(() => this.next(), this.intervalValue);
    }

    stop() {
        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    }

    next() {
        this.show((this.index + 1) % this.slideTargets.length);
    }

    goTo(event) {
        this.show(Number(event.currentTarget.dataset.heroSliderIndexParam));
        this.start();
    }

    show(index) {
        this.index = index;

        this.slideTargets.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
        });

        this.dotTargets.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });
    }
}
