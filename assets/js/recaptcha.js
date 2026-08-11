/**
 * Google reCAPTCHA v3 Frontend Helper
 * 
 * Intercepts form submissions to generate reCAPTCHA v3 tokens immediately
 * before submission, storing the token in a `g-recaptcha-response` field.
 */
(function(window, document) {
    'use strict';

    /**
     * Generate a reCAPTCHA v3 token for a given action.
     * 
     * @param {string} siteKey - Google reCAPTCHA v3 Site Key
     * @param {string} action - Action name (e.g. 'login', 'register', 'contact')
     * @returns {Promise<string>} Token promise
     */
    window.getRecaptchaToken = function(siteKey, action) {
        return new Promise(function(resolve, reject) {
            if (typeof window.grecaptcha === 'undefined') {
                console.warn('[reCAPTCHA v3] grecaptcha SDK not loaded.');
                resolve('');
                return;
            }

            window.grecaptcha.ready(function() {
                window.grecaptcha.execute(siteKey, { action: action || 'submit' })
                    .then(function(token) {
                        resolve(token || '');
                    })
                    .catch(function(err) {
                        console.error('[reCAPTCHA v3] Execution error:', err);
                        resolve('');
                    });
            });
        });
    };

    /**
     * Attach automatic reCAPTCHA token generation to a form element.
     * 
     * @param {HTMLFormElement} form 
     * @param {string} siteKey 
     * @param {string} action 
     */
    window.attachRecaptchaToForm = function(form, siteKey, action) {
        if (!form || !siteKey) return;

        form.addEventListener('submit', function(e) {
            if (form.dataset.recaptchaExecuted === 'true') {
                // Token already fetched for this submission attempt
                form.dataset.recaptchaExecuted = 'false';
                return;
            }

            // Prevent submission to fetch fresh token immediately before submit
            e.preventDefault();

            window.getRecaptchaToken(siteKey, action).then(function(token) {
                var input = form.querySelector('input[name="g-recaptcha-response"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'g-recaptcha-response';
                    form.appendChild(input);
                }
                input.value = token;

                // Mark executed and re-trigger form submit
                form.dataset.recaptchaExecuted = 'true';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    };

    // Auto-attach to any forms with data-recaptcha-sitekey & data-recaptcha-action
    document.addEventListener('DOMContentLoaded', function() {
        var forms = document.querySelectorAll('form[data-recaptcha-sitekey][data-recaptcha-action]');
        forms.forEach(function(form) {
            var siteKey = form.getAttribute('data-recaptcha-sitekey');
            var action = form.getAttribute('data-recaptcha-action');
            if (siteKey && action) {
                window.attachRecaptchaToForm(form, siteKey, action);
            }
        });
    });
})(window, document);
