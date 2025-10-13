/* 05-2023 by LOMART */

/*
Forme du HTML attendu
---------------------
<div id="up-98-4">
	<div class="uprm-panel inactive">
		<div class="uprm-overlay"></div>
		... le contenu masqué ...
	</div>
	<div class="uprm-btns">
		<a class="uprm-btn-more">lire la suite</a>
		<a class="uprm-btn-less inactive">replier</a>
	</div>
</div>
*/

"use strict";

function readmore(selector, options) {
	document.addEventListener("DOMContentLoaded", function() {
		let defaults = {
			panelMinHeight: '0px', // hauteur du panel quand enroulé
			panelMinSelector: '', // sélecteur CSS sur la partie visible quand enroulé
			panelEvent: '', // evenement sur contenu visible pour dérouler/enrouler le contenu
		}
		Object.assign(defaults, options);

		let panelContent = document.querySelector(selector + ' .uprm-panel');
		let panelOverlay = document.querySelector(selector + ' .uprm-overlay');
		let btnMore = document.querySelector(selector + ' .uprm-btn-more');
		let btnLess = document.querySelector(selector + ' .uprm-btn-less');
		// la hauteur minimale est remplacé par celle du bloc indiqué 
		if (defaults.panelMinSelector && panelContent.querySelector(defaults.panelMinSelector)) {
			defaults.panelMinHeight = panelContent.querySelector(defaults.panelMinSelector).clientHeight + "px"
		}
		panelContent.style.minHeight = defaults.panelMinHeight;


		function showPanel() {
			panelContent.classList.remove('inactive');
			btnMore.classList.add('inactive');
			btnLess.classList.remove('inactive');
			if (panelOverlay)
				panelOverlay.style.display = 'none';
			panelContent.style.height = 'auto';
			let height = panelContent.clientHeight + "px"
			panelContent.style.height = defaults.panelMinHeight;
			setTimeout(() => {
				panelContent.style.height = height
			}, 0)

		}

		function hidePanel() {
			panelContent.classList.add('inactive');
			btnMore.classList.remove('inactive');
			btnLess.classList.add('inactive');

			panelContent.style.height = defaults.panelMinHeight;
			panelContent.addEventListener('transitionend', () => {
				panelContent.classList.remove('active')
			}, { once: true })

			if (panelOverlay) {
				panelOverlay.style.display = 'block';
			}
		}

		function tooglePanel() {
			if (panelContent.classList.contains('inactive')) {
				showPanel();
			} else {
				hidePanel();
			}
		}

		btnMore.addEventListener('click', showPanel);
		btnLess.addEventListener('click', hidePanel);
		if (defaults.panelEvent) {
			panelContent.style.pointerEvents = 'all';
			//panelContent.style.userSelect = 'none';
			panelContent.addEventListener(defaults.panelEvent, tooglePanel);
		}
	});
};

