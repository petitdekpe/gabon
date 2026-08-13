import { Controller } from '@hotwired/stimulus';
import { isValidPhoneNumber, parsePhoneNumberFromString } from 'libphonenumber-js';
import { AFRICAN_PHONE_COUNTRIES, GABON_PHONE_COUNTRY, findPhoneCountry, flagEmoji } from '../data/phone_countries.js';

/**
 * Écran 1 « Votre identité » : durée de séjour, statut des documents,
 * bascule "carte de résident non concerné", validation progressive
 * avant soumission, et assistance de saisie des numéros de téléphone
 * (indicatif par pays, placeholder, formatage, validation).
 */
export default class extends Controller {
    static targets = [
        'form',
        'firstEntryDateInput',
        'stayDuration',
        'passportExpiresAtWrapper',
        'residentCardExpiresAtWrapper',
        'consularCardExpiresAtWrapper',
        'passportBadge',
        'residentCardBadge',
        'consularCardBadge',
        'professionOtherWrapper',
        'residentCardNotApplicable',
        'residentCardFields',
        'countrySelect',
        'localPhoneCountrySelect',
        'localPhoneField',
        'gabonContactCountrySelect',
        'gabonContactField',
    ];

    connect() {
        this.updateStayDuration();
        this.syncDocumentBadge('passport');
        this.syncDocumentBadge('residentCard');
        this.syncDocumentBadge('consularCard');
        this.initPhoneCountries();
    }

    // --- 1. Durée de séjour --------------------------------------------------

    updateStayDuration() {
        if (!this.hasFirstEntryDateInputTarget || !this.hasStayDurationTarget) {
            return;
        }

        const value = this.firstEntryDateInputTarget.value;
        if (!value) {
            this.stayDurationTarget.textContent = '—';
            return;
        }

        const entryDate = new Date(`${value}T00:00:00`);
        if (Number.isNaN(entryDate.getTime())) {
            this.stayDurationTarget.textContent = '—';
            return;
        }

        this.stayDurationTarget.textContent = this.formatDuration(entryDate, new Date());
    }

    formatDuration(from, to) {
        if (from > to) {
            return 'Date future';
        }

        let years = to.getFullYear() - from.getFullYear();
        let months = to.getMonth() - from.getMonth();
        let days = to.getDate() - from.getDate();

        if (days < 0) {
            months -= 1;
            const daysInPreviousMonth = new Date(to.getFullYear(), to.getMonth(), 0).getDate();
            days += daysInPreviousMonth;
        }

        if (months < 0) {
            years -= 1;
            months += 12;
        }

        const parts = [];
        if (years > 0) {
            parts.push(`${years} an${years > 1 ? 's' : ''}`);
        }
        if (months > 0) {
            parts.push(`${months} mois`);
        }
        if (days > 0 || parts.length === 0) {
            parts.push(`${days} jour${days > 1 ? 's' : ''}`);
        }

        return parts.join(' ');
    }

    // --- 1bis. Téléphones (indicatif pays, placeholder, formatage) -------------

    buildPhoneCountryOption(country) {
        const option = document.createElement('md-select-option');
        option.value = country.code;
        const headline = document.createElement('div');
        headline.slot = 'headline';
        headline.textContent = `${flagEmoji(country.code)} ${country.name} (+${country.callingCode})`;
        option.appendChild(headline);

        return option;
    }

    async initPhoneCountries() {
        if (this.hasLocalPhoneCountrySelectTarget) {
            const select = this.localPhoneCountrySelectTarget;

            AFRICAN_PHONE_COUNTRIES.forEach((country) => {
                select.appendChild(this.buildPhoneCountryOption(country));
            });

            // Le select Material Web (LitElement) n'assigne ses options slottées
            // qu'après son propre cycle de rendu : attendre updateComplete avant
            // de fixer la valeur, sinon elle est silencieusement ignorée.
            await select.updateComplete;

            const defaultCode = this.hasCountrySelectTarget ? this.countrySelectTarget.value : null;
            const resolvedCode = findPhoneCountry(defaultCode) ? defaultCode : 'BJ';
            select.value = resolvedCode;

            await select.updateComplete;
            this.updateLocalPhonePlaceholder(resolvedCode);
        }

        if (this.hasGabonContactCountrySelectTarget && GABON_PHONE_COUNTRY) {
            const select = this.gabonContactCountrySelectTarget;
            select.appendChild(this.buildPhoneCountryOption(GABON_PHONE_COUNTRY));

            await select.updateComplete;
            select.value = GABON_PHONE_COUNTRY.code;
        }

        if (this.hasGabonContactFieldTarget && GABON_PHONE_COUNTRY) {
            this.gabonContactFieldTarget.placeholder = GABON_PHONE_COUNTRY.placeholder;
        }
    }

    syncLocalPhoneCountry(event) {
        if (!this.hasLocalPhoneCountrySelectTarget) {
            return;
        }

        const code = event.currentTarget.value;
        if (findPhoneCountry(code)) {
            this.localPhoneCountrySelectTarget.value = code;
            this.updateLocalPhonePlaceholder(code);
        }
    }

    updateLocalPhonePlaceholder(code = this.localPhoneCountrySelectTarget?.value) {
        if (!this.hasLocalPhoneCountrySelectTarget || !this.hasLocalPhoneFieldTarget) {
            return;
        }

        const country = findPhoneCountry(code);
        if (!country) {
            return;
        }

        this.localPhoneFieldTarget.placeholder = country.placeholder;

        if (!this.localPhoneFieldTarget.value?.trim()) {
            this.localPhoneFieldTarget.value = `+${country.callingCode}`;
        }
    }

    formatPhoneInput(event) {
        const field = event.currentTarget;
        const value = (field.value ?? '').trim();
        if (!value) {
            return;
        }

        const region = field === this.gabonContactFieldTarget
            ? 'GA'
            : this.localPhoneCountrySelectTarget?.value;

        // Normalise au format E.164 (celui stocké et validé côté serveur) une
        // fois la saisie terminée, quelle que soit la façon dont l'utilisateur
        // a tapé son numéro (avec ou sans "+", avec ou sans le zéro national).
        const parsed = parsePhoneNumberFromString(value, region);
        if (parsed) {
            field.value = parsed.number;
        }
    }

    validatePhoneField(field, region, invalidFields) {
        if (!field || field.disabled) {
            return;
        }

        const value = (field.value ?? '').trim();
        if (value === '' || isValidPhoneNumber(value, region)) {
            field.error = false;
            return;
        }

        field.error = true;
        field.errorText = 'Ce numéro de téléphone n’est pas valide.';
        invalidFields.push(field);
    }

    // --- 2. Statut des documents ----------------------------------------------

    updateDocumentBadges(event) {
        const docType = event.currentTarget.dataset.docType;
        if (!docType) {
            return;
        }

        this.syncDocumentBadge(docType, event.currentTarget.value);
    }

    syncDocumentBadge(docType, explicitValue = null) {
        const wrapperTarget = `${docType}ExpiresAtWrapper`;
        const badgeTarget = `${docType}Badge`;

        if (!this[`has${this.capitalize(wrapperTarget)}Target`] || !this[`has${this.capitalize(badgeTarget)}Target`]) {
            return;
        }

        const wrapper = this[`${wrapperTarget}Target`];
        const badge = this[`${badgeTarget}Target`];
        const field = wrapper.querySelector('md-outlined-text-field');
        const value = explicitValue ?? field?.value;

        const status = this.computeDocumentStatus(value);
        badge.textContent = status.label;
        badge.className = `gab-badge gab-badge--${status.level}`;
    }

    computeDocumentStatus(value) {
        if (!value) {
            return { level: 'unknown', label: 'À renseigner' };
        }

        const expiresAt = new Date(`${value}T00:00:00`);
        if (Number.isNaN(expiresAt.getTime())) {
            return { level: 'unknown', label: 'À renseigner' };
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const diffDays = Math.round((expiresAt.getTime() - today.getTime()) / 86_400_000);

        if (diffDays < 0) {
            return { level: 'expired', label: 'Expirée' };
        }

        if (diffDays <= 90) {
            return { level: 'warning', label: `En cours d'expiration (J-${diffDays})` };
        }

        return { level: 'valid', label: 'En cours de validité' };
    }

    // --- 3. Profession "Autre" -------------------------------------------------

    toggleProfessionOther(event) {
        if (!this.hasProfessionOtherWrapperTarget) {
            return;
        }

        const isOther = event.currentTarget.value === 'other';
        this.professionOtherWrapperTarget.hidden = !isOther;
    }

    // --- 3bis. Carte de résident "Non concerné" ---------------------------------

    toggleResidentCard(event) {
        if (!this.hasResidentCardFieldsTarget) {
            return;
        }

        const notApplicable = event.currentTarget.checked;
        this.residentCardFieldsTarget.hidden = notApplicable;

        this.residentCardFieldsTarget
            .querySelectorAll('md-outlined-text-field, md-outlined-select, input[type="file"]')
            .forEach((field) => {
                field.disabled = notApplicable;
                if (notApplicable && 'value' in field) {
                    field.value = '';
                }
            });

        if (notApplicable) {
            this.syncDocumentBadge('residentCard', '');
        }
    }

    // --- Upload : preview + drag & drop -----------------------------------------

    previewFileName(event) {
        const input = event.currentTarget;
        const zone = input.closest('.gab-upload');
        const nameEl = zone?.querySelector('.gab-upload__filename');
        if (nameEl) {
            nameEl.textContent = input.files?.[0]?.name ?? '';
        }
    }

    dragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('gab-upload--dragover');
    }

    dragLeave(event) {
        event.currentTarget.classList.remove('gab-upload--dragover');
    }

    drop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('gab-upload--dragover');

        const input = event.currentTarget.querySelector('input[type="file"]');
        if (input && event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // --- 4. Validation progressive -----------------------------------------------

    validateBeforeSubmit(event) {
        const invalidFields = [];

        this.formTarget.querySelectorAll('md-outlined-text-field[required], md-outlined-select[required]').forEach((field) => {
            if (field.disabled) {
                return;
            }

            const value = (field.value ?? '').toString().trim();
            if (value === '') {
                field.error = true;
                field.errorText = 'Ce champ est requis.';
                invalidFields.push(field);
            } else {
                field.error = false;
            }
        });

        this.validatePhoneField(this.hasLocalPhoneFieldTarget ? this.localPhoneFieldTarget : null, this.localPhoneCountrySelectTarget?.value, invalidFields);
        this.validatePhoneField(this.hasGabonContactFieldTarget ? this.gabonContactFieldTarget : null, 'GA', invalidFields);

        this.formTarget.querySelectorAll('input[type="file"][required]').forEach((input) => {
            if (input.disabled) {
                return;
            }

            const zone = input.closest('.gab-upload');
            const dropzone = zone?.querySelector('.gab-upload__dropzone');

            if (!input.files || input.files.length === 0) {
                dropzone?.classList.add('gab-upload__dropzone--error');
                invalidFields.push(input);
            } else {
                dropzone?.classList.remove('gab-upload__dropzone--error');
            }
        });

        if (invalidFields.length > 0) {
            event.preventDefault();
            const first = invalidFields[0];
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof first.focus === 'function') {
                first.focus();
            }
        }
    }

    capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }
}
