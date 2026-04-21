/**
 * Système de validation de formulaires personnalisé
 * Valide les champs en temps réel et à la soumission
 */

class FormValidator {
    constructor(formSelector = '.form-validation') {
        this.form = document.querySelector(formSelector);
        if (!this.form) return;

        this.rules = {
            quantite: {
                type: 'number',
                min: 0,
                step: 0.01,
                messages: {
                    required: 'La quantité est obligatoire.',
                    invalid: 'La quantité doit être un nombre valide.',
                    min: 'La quantité doit être supérieure ou égale à 0.',
                }
            },
            'date_entree': {
                type: 'date',
                messages: {
                    required: 'La date d\'entrée est obligatoire.',
                    invalid: 'La date d\'entrée n\'est pas valide.',
                }
            },
            'date_expiration': {
                type: 'date',
                messages: {
                    required: 'La date d\'expiration est obligatoire.',
                    invalid: 'La date d\'expiration n\'est pas valide.',
                }
            },
            'id_produit': {
                type: 'select',
                messages: {
                    required: 'Sélectionnez un produit.',
                }
            },
            'id_user': {
                type: 'number',
                min: 1,
                messages: {
                    required: 'L\'ID utilisateur est obligatoire.',
                    invalid: 'L\'ID utilisateur doit être un entier positif.',
                    min: 'L\'ID utilisateur doit être supérieur à 0.',
                }
            },
            nom: {
                type: 'text',
                minlength: 2,
                maxlength: 100,
                messages: {
                    required: 'Le nom du produit est obligatoire.',
                    minlength: 'Le nom doit contenir au moins 2 caractères.',
                    maxlength: 'Le nom ne doit pas dépasser 100 caractères.',
                }
            },
            type: {
                type: 'text',
                minlength: 2,
                maxlength: 50,
                messages: {
                    required: 'Le type est obligatoire.',
                    minlength: 'Le type doit contenir au moins 2 caractères.',
                    maxlength: 'Le type ne doit pas dépasser 50 caractères.',
                }
            },
            unite: {
                type: 'text',
                minlength: 1,
                maxlength: 20,
                messages: {
                    required: 'L\'unité est obligatoire.',
                    minlength: 'L\'unité doit contenir au moins 1 caractère.',
                    maxlength: 'L\'unité ne doit pas dépasser 20 caractères.',
                }
            },
            'prix_unitaire': {
                type: 'number',
                min: 0,
                step: 0.01,
                messages: {
                    required: 'Le prix unitaire est obligatoire.',
                    invalid: 'Le prix unitaire doit être un nombre valide.',
                    min: 'Le prix unitaire doit être supérieur ou égal à 0.',
                }
            }
        };

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.form.addEventListener('submit', (e) => this.onSubmit(e));
    }

    attachEventListeners() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const fieldName = input.getAttribute('id')?.replace(/^[^_]*_/, '') || input.name;

            // Validation au départ du champ (blur)
            input.addEventListener('blur', () => {
                this.validateField(input);
            });

            // Validation en temps réel (input)
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });

            // Validation en temps réel (change) pour les sélects
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', () => {
                    if (input.classList.contains('is-invalid')) {
                        this.validateField(input);
                    }
                });
            }
        });
    }

    validateField(field) {
        const fieldName = field.getAttribute('id')?.replace(/^[^_]*_/, '') || field.name;
        const rule = this.rules[fieldName];
        const value = field.value.trim();

        // Réinitialiser les classes d'erreur
        field.classList.remove('is-invalid', 'is-valid');

        // Vérifier si le champ est requis
        if (!value) {
            if (field.hasAttribute('required') || rule) {
                field.classList.add('is-invalid');
                this.showError(field, (rule?.messages?.required) || 'Ce champ est obligatoire.');
                return false;
            } else {
                field.classList.remove('is-invalid');
                this.clearError(field);
                return true;
            }
        }

        // Si pas de règle, c'est valide
        if (!rule) {
            field.classList.add('is-valid');
            this.clearError(field);
            return true;
        }

        let isValid = true;
        let errorMessage = '';

        // Validation par type
        switch (rule.type) {
            case 'number':
                if (isNaN(value) || value === '') {
                    isValid = false;
                    errorMessage = rule.messages.invalid;
                } else {
                    const numValue = parseFloat(value);
                    if (rule.min !== undefined && numValue < rule.min) {
                        isValid = false;
                        errorMessage = rule.messages.min;
                    }
                }
                break;

            case 'date':
                if (!this.isValidDate(value)) {
                    isValid = false;
                    errorMessage = rule.messages.invalid;
                }
                break;

            case 'text':
                if (rule.minlength && value.length < rule.minlength) {
                    isValid = false;
                    errorMessage = rule.messages.minlength;
                } else if (rule.maxlength && value.length > rule.maxlength) {
                    isValid = false;
                    errorMessage = rule.messages.maxlength;
                }
                break;

            case 'select':
                if (!value || value === '') {
                    isValid = false;
                    errorMessage = rule.messages.required;
                }
                break;
        }

        // Validation spéciale: dates cohérentes (expiration >= entrée)
        if (fieldName === 'date_expiration') {
            const dateEntreeInput = this.form.querySelector('#stock_date_entree, #produit_date_entree');
            if (dateEntreeInput && dateEntreeInput.value) {
                const dateEntree = new Date(dateEntreeInput.value);
                const dateExpiration = new Date(value);
                if (dateExpiration < dateEntree) {
                    isValid = false;
                    errorMessage = 'La date d\'expiration doit être supérieure ou égale à la date d\'entrée.';
                }
            }
        }

        if (isValid) {
            field.classList.add('is-valid');
            this.clearError(field);
        } else {
            field.classList.add('is-invalid');
            this.showError(field, errorMessage);
        }

        return isValid;
    }

    isValidDate(dateString) {
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateString)) return false;

        const date = new Date(dateString);
        const timestamp = date.getTime();

        if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
            return false;
        }

        return dateString === date.toISOString().split('T')[0];
    }

    showError(field, message) {
        let errorContainer = field.nextElementSibling;

        // Trouver ou créer le conteneur d'erreur
        if (!errorContainer || !errorContainer.classList.contains('invalid-feedback-js')) {
            // Chercher s'il existe déjà
            const existingError = field.parentElement.querySelector('.invalid-feedback-js');
            if (existingError) {
                existingError.remove();
            }

            errorContainer = document.createElement('div');
            errorContainer.className = 'invalid-feedback-js d-block mt-2';
            field.parentElement.appendChild(errorContainer);
        }

        errorContainer.innerHTML = `
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-exclamation-circle me-2" style="color: #e74c3c;"></i>
                <span>${message}</span>
            </div>
        `;
    }

    clearError(field) {
        const errorContainer = field.parentElement.querySelector('.invalid-feedback-js');
        if (errorContainer) {
            errorContainer.remove();
        }
    }

    onSubmit(e) {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        let isFormValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            e.preventDefault();
            e.stopPropagation();

            // Scroll vers le premier champ invalide
            const firstInvalidField = this.form.querySelector('.is-invalid');
            if (firstInvalidField) {
                firstInvalidField.focus();
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        this.form.classList.add('was-validated');
    }
}

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    new FormValidator('.form-validation');
});
