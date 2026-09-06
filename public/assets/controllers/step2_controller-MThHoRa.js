import { Controller } from '@hotwired/stimulus';

/**
 * Écran 2 « Votre profil » : sélection du profil (étudiant/salarié/entrepreneur),
 * affichage conditionnel de la section correspondante, champs répétables
 * (CollectionType), et validation progressive avant soumission.
 */
export default class extends Controller {
    static targets = [
        'section',
        'profileSelectionError',
        'lastDegreeOtherWrapper',
        'employeeSectorOtherWrapper',
        'entrepreneurSectorOtherWrapper',
    ];

    connect() {
        this.element.querySelectorAll('.gab-collection').forEach((wrapper) => this.syncCollectionAddButton(wrapper));
    }

    // --- Sélecteur de profil ---------------------------------------------------

    onProfileTypeChange(event) {
        const value = event.currentTarget.value;

        if (this.hasProfileSelectionErrorTarget) {
            this.profileSelectionErrorTarget.hidden = true;
        }

        this.sectionTargets.forEach((section) => {
            section.hidden = section.dataset.profile !== value;
        });

        fetch('/register/profile-type', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ profileType: value }),
        }).catch(() => {
            // Sauvegarde silencieuse : une panne réseau ici ne doit pas bloquer l'utilisateur,
            // le choix sera de toute façon renvoyé avec la soumission finale du formulaire.
        });
    }

    // --- Champ "Autre" conditionnel (diplôme / secteur d'activité) -------------

    toggleOtherField(event) {
        const targetName = event.currentTarget.dataset.otherTarget;
        if (!targetName) {
            return;
        }

        const hasGetter = `has${this.capitalize(targetName)}Target`;
        if (!this[hasGetter]) {
            return;
        }

        const isOther = event.currentTarget.value === 'other';
        this[`${targetName}Target`].hidden = !isOther;
    }

    // --- CollectionType répétable (max 4 lignes) --------------------------------

    addCollectionItem(event) {
        const wrapper = event.currentTarget.closest('.gab-collection');
        if (!wrapper) {
            return;
        }

        const itemsContainer = wrapper.querySelector('[data-step2-target="collectionItems"]');
        const max = parseInt(wrapper.dataset.max, 10) || 4;

        if (itemsContainer.children.length >= max) {
            return;
        }

        const index = itemsContainer.children.length;
        const html = wrapper.dataset.prototype.replaceAll('__name__', String(index));

        const template = document.createElement('template');
        template.innerHTML = html.trim();
        itemsContainer.appendChild(template.content.firstElementChild);

        this.syncCollectionAddButton(wrapper);
    }

    removeCollectionItem(event) {
        const wrapper = event.currentTarget.closest('.gab-collection');
        const item = event.currentTarget.closest('.gab-collection__item');
        item?.remove();

        if (wrapper) {
            this.syncCollectionAddButton(wrapper);
        }
    }

    syncCollectionAddButton(wrapper) {
        const itemsContainer = wrapper.querySelector('[data-step2-target="collectionItems"]');
        const addButton = wrapper.querySelector('[data-step2-target="collectionAddButton"]');
        const max = parseInt(wrapper.dataset.max, 10) || 4;

        if (addButton) {
            addButton.disabled = itemsContainer.children.length >= max;
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

    // --- Validation progressive avant soumission --------------------------------

    validateBeforeSubmit(event) {
        const form = event.currentTarget;
        const invalidFields = [];

        const hasProfileSelected = Array.from(this.element.querySelectorAll('md-radio[name="profileTypeSelector"]')).some((radio) => radio.checked);
        if (!hasProfileSelected) {
            event.preventDefault();
            if (this.hasProfileSelectionErrorTarget) {
                this.profileSelectionErrorTarget.hidden = false;
                this.profileSelectionErrorTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        form.querySelectorAll('md-outlined-text-field[required], md-outlined-select[required]').forEach((field) => {
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

        form.querySelectorAll('.gab-radio-group[data-required="1"]').forEach((group) => {
            const name = group.dataset.radioName;
            const anyChecked = Array.from(form.querySelectorAll('md-radio')).some((radio) => radio.name === name && radio.checked);
            const errorEl = group.querySelector('[data-radio-error]');

            if (!anyChecked) {
                if (errorEl) {
                    errorEl.textContent = 'Merci de répondre à cette question.';
                }
                invalidFields.push(group);
            } else if (errorEl) {
                errorEl.textContent = '';
            }
        });

        form.querySelectorAll('input[type="file"][required]').forEach((input) => {
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

        const consentCheckbox = form.querySelector('md-checkbox[name="consentGiven"]');
        const consentRow = consentCheckbox?.closest('.gab-checkbox-row');
        if (consentCheckbox && !consentCheckbox.checked) {
            invalidFields.push(consentCheckbox);
            consentRow?.classList.add('gab-checkbox-row--error');
        } else {
            consentRow?.classList.remove('gab-checkbox-row--error');
        }

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
