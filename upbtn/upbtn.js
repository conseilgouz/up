/**
 * @package upbtn 
 * @version 5.4 - 14/11/2025
 * @author Lomart
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

/*
 * v5.2
 * - ajout champ recherche
 * - ajout bouton 'insere SO+SF'
 * - br pour numpart pour faciliter wysiwyg
*/

'use strict';

var httpRequest, waitaction, waitlang;

/**
* Construit et insère le(s) shortcodes
*/
function insertShortcode(editor, actionName, options, forceClose = false, numParts = 1) {
    const openSC = `{up ${options}}`;
    const closeSC = `{/up ${actionName}}`;
    let parts = '';

    for (let i = 1; i < numParts; i++) {
        parts += `<br><br>{======= ${i + 1}}\n`;
    }

    if (numParts > 1) {
        parts += `<br><br>`;
        forceClose = true;
    }
    const parent = window.parent;
    let out = openSC;
    let selContent = '';
    const iframe = parent.document.getElementById("jform_articletext_ifr");

    if (iframe) {
        const selection = iframe.contentWindow.document.getSelection();
        selContent = selection ? selection.toString() : '';
        if (selContent || forceClose) out += selContent + parts + closeSC;
    } else if (forceClose) {
        out += parts + closeSC;
    }

    const editors = parent.Joomla?.editors?.instances;
    if (editors && editors[editor]) {
        editors[editor].replaceSelection(out);
    } else {
        parent.jInsertEditorText(out, editor);
    }

    const modal = parent.Joomla?.Modal?.getCurrent?.();
    if (modal) {
        modal.close();
    } else {
        parent.jModalClose?.();
    }
}

/**
 * Lance une requête ajax pour recuperer les infos d'une action
 */
function loadOptionsAction(actionname, lang) {
    if (!actionname || !lang) {
        console.error('Invalid parameters for loadOptionsAction');
        return;
    }

    httpRequest = new XMLHttpRequest();

    if (!httpRequest) {
        console.error('Failed to create an XMLHttpRequest instance');
        alert('Error: Unable to create an XMLHttpRequest instance');
        return;
    }

    httpRequest.onload = showReponse;
    httpRequest.onerror = () => {
        console.error(`Network error while trying to load options for action: ${actionname}`);
        alert('Network error occurred. Please try again.');
    };

    const url = `../actions/${actionname}/up/${lang}.${actionname}.html`;
    httpRequest.open('GET', url, true);
    httpRequest.send();
}

/**
 * Recupère la requête ajax et affiche
 */
function showReponse() {
    if (httpRequest.readyState !== XMLHttpRequest.DONE) return;

    if (httpRequest.status === 200) {
        const ajaxOptions = document.getElementById('ajax-options');
        const optionsContainer = document.getElementById('upbtn-options');
        const footerContainer = document.getElementById('upbtn-footer');

        if (ajaxOptions) ajaxOptions.innerHTML = httpRequest.responseText;
        if (optionsContainer) optionsContainer.style.display = 'block';
        if (footerContainer) footerContainer.style.display = 'block';
    } else if (httpRequest.status === 404) {// mini UP 
        let btns = document.getElementById('upbtns');
        let wait = document.getElementById('page-load-status');
        btns.style.display ='none';
        wait.style.display='block';
        let tmpurl = httpRequest.responseURL.split('/');
        let actionpart = tmpurl[tmpurl.length - 1].split('.');
        waitaction = actionpart[1];
        waitlang = actionpart[0];
        let loc = window.location.pathname.split('/');
        let url = window.location.origin+'/'+loc[1]+'/administrator/index.php?option=com_ajax&group=content&plugin=up&exist='+waitaction+'&format=json';
        httpRequest = new XMLHttpRequest();
        httpRequest.open('GET',url,true);
        httpRequest.onload = loadReponse;
        httpRequest.onerror = () => {
            console.error(`Network error while trying to load options for action: ${waitaction}`);
            alert('Network error occurred. Please try again.');
        };
        httpRequest.send();
    } else {
        console.error(`Error: File not found at ${httpRequest.responseURL}`);
        alert(`Error: File not found at ${httpRequest.responseURL}`);
    }
}
// reponse from UP on a action load request
function loadReponse() {
    if (httpRequest.readyState !== XMLHttpRequest.DONE) return;

    let btns = document.getElementById('upbtns');
    let wait = document.getElementById('page-load-status');
    wait.style.display ='none';
    btns.style.display= 'block';

    if (httpRequest.status === 200) {
        let resp = JSON.parse(httpRequest.response);
        if (resp.data[0]) {
            loadOptionsAction(waitaction,waitlang);
        } else {
            alert(`Error: not found ${waitaction}`);
        }
    } else {
        console.error(`Error: File not found at ${httpRequest.responseURL}`);
        alert(`Error: File not found at ${httpRequest.responseURL}`);
    }
}

/*
 *  Boucle de traitement
 */

document.addEventListener('DOMContentLoaded', () => {
    const lang = document.getElementById('upbtn-lang').value;
    const actionSelect = document.getElementById('upbtn-actionname');
    const optionsContainer = document.getElementById('upbtn-options');
    const footerContainer = document.getElementById('upbtn-footer');
    const helpCheckbox = document.getElementById('upbtn-help');
    const debugCheckbox = document.getElementById('upbtn-debug');
    const numPartsInput = document.getElementById('upbtn-nbparts');
    const form = document.getElementById('upbtn-form');
    let actionname;

    // ouvre la liste des actions
    actionSelect.size = 25;
    actionSelect.focus();

    // ===== Charge et affiche le masque de l'option sélectionné
    actionSelect.addEventListener('change', () => {
        actionSelect.size = 0; // reduit la liste des actions
        if (actionSelect.value) {
            loadOptionsAction(actionSelect.value, lang);
            actionname = actionSelect.value;
        } else {
            optionsContainer.innerHTML = '';
            optionsContainer.style.display = 'none';
            footerContainer.style.display = 'none';
        }
    });

    // ===== traitement des 2 boutons insérer 
    const validShortcode = (forceClose = false) => {
        if (!actionname) return;

        let options = actionname.replaceAll('_', '-');
        const actionValue = document.getElementById(actionname)?.value;

        if (actionValue) options += `=${actionValue}`;

        Array.from(form.elements).forEach(e => {
            if (e.name && e.value && e.value !== e.title) {
                if (!(e.type === 'color' && e.value === '#feffff' && !e.title)) {
                    options += ` | ${e.name}=${e.value}`;
                }
            }
        });

        if (helpCheckbox.checked) options += ' | ?';
        if (debugCheckbox.checked) options += ' | debug';

        const numParts = numPartsInput.value;
        insertShortcode('jform_articletext', actionname.replaceAll('_', '-'), options, forceClose, numParts);
    };

    document.getElementById('upbtn-submit').addEventListener('click', () => validShortcode(false));
    document.getElementById('upbtn-submitAll').addEventListener('click', () => validShortcode(true));

    // ===== FILTRAGE
    const searchInput = document.getElementById('upbtn-filter-input');
    const dropdown = document.getElementById('upbtn-actionname');
    const selectOptions = Array.from(dropdown.options);

    searchInput.addEventListener('input', function () {
        const query = searchInput.value.toLowerCase();

        selectOptions.forEach(option => {
            const text = option.textContent.toLowerCase();
            option.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // ===== focus sur filtrage 
    setTimeout(()=> {
        document.getElementById('upbtn-filter-input').focus();
    },500);

});
