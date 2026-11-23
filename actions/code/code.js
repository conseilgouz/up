/* Action UP : code - copie deleguee, compatible avec plusieurs blocs par page. */
(function () {
    'use strict';

    if (window.upCodeCopyInitialized) {
        return;
    }
    window.upCodeCopyInitialized = true;

    function legacyCopy(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '0';
        document.body.appendChild(textarea);
        textarea.select();
        var success = false;
        try {
            success = document.execCommand('copy');
        } catch (error) {
            success = false;
        }
        document.body.removeChild(textarea);
        return success;
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.up-code__copy');
        if (!button) {
            return;
        }

        var wrapper = button.closest('.up-code');
        var code = wrapper ? wrapper.querySelector('.up-code__pre code') : null;
        if (!code) {
            return;
        }

        var text = code.textContent.replace(/\r\n?/g, '\n');
        var status = wrapper.querySelector('.up-code__status');
        var label = button.querySelector('.up-code__copy-label');

        function feedback(success) {
            if (label) {
                label.textContent = success ? 'Copié !' : 'Copie impossible';
            }
            if (status) {
                status.textContent = success ? 'Code copié dans le presse-papiers.' : 'La copie a échoué.';
            }
            window.setTimeout(function () {
                if (label) {
                    label.textContent = 'Copier';
                }
            }, 1600);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                feedback(true);
            }).catch(function () {
                feedback(legacyCopy(text));
            });
        } else {
            feedback(legacyCopy(text));
        }
    });
})();
