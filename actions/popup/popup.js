/* 05-2023 by LOMART */
"use strict";

function popup(selector, options) {
	document.addEventListener("DOMContentLoaded", function() {

		let defaults = {
			activationMode: 'time', // mode d'activation: time, scroll ou position 
			activationValue: '', // valeur pour déclenchement (ms, pourcentage ou selecteur css)
			scrollOffsetTop: 3, // décalage haut pour scroll
			scrollOffsetBottom: 3, // décalage bas pour scroll 
			closeOnlyButton: false, // fermeture du popup en cliquant hors du bouton
			bodyBlocked: true, // le scroll sur la page est bloqué
			cookieName: 'upPopup', // nom du cookie
			cookieDuration: 0, // Durée du cookie en jours ou 0 (session) ou -1 (aucun)
			animIn: '', // 
			animOut: '', // 
			animtarget: 'popup', // overlay sinon 
		};

		Object.assign(defaults, options);
		// console.log('defaults:', defaults);

		const main = document.querySelector(selector);
		let anim;
		if (defaults.animtarget == 'popup') {
			anim = document.querySelector(selector + ' .popup-content');
		} else {
			anim = document.querySelector(selector + ' .popup-overlay');
		}
		const btnClose = document.querySelector(selector + ' .popup-close');

		let posMin, posMax; // intervalle de déclenchement pour scroll

		// ============== gestion cookie

		// https://ppk.developpez.com/tutoriels/javascript/gestion-cookies-javascript/
		function createCookie(name, value, days) {
			let expires = '';
			if (days) {
				let date = new Date();
				date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
				expires = "; expires=" + date.toGMTString();
			}
			document.cookie = name + "=" + value + expires + "; path=/";
		}

		function readCookie(name) {
			let nameEQ = name + "=";
			let ca = document.cookie.split(';');
			for (let i = 0; i < ca.length; i++) {
				let c = ca[i];
				while (c.charAt(0) == ' ') c = c.substring(1, c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
			}
			return null;
		}

		function eraseCookie(name) {
			createCookie(name, "", -1);
		}

		// ============== affichage ou masquage du popup

		let hidePopup = function() {
			if (defaults.animIn) anim.classList.remove(defaults.animIn);
			if (defaults.animOut) {
				anim.classList.add(defaults.animOut);
				anim.addEventListener('animationend', function() { main.style.display = 'none'; });
			} else {
				main.style.display = 'none';
			}
			// annule overflow
			document.body.style.overflow = null;
		}

		let showPopup = function() {
			console.log('showPopup');
			// la classe animIn doit être ajouté par le php
			main.style.display = null;
			if (defaults.bodyBlocked) document.body.style.overflow = 'hidden';
		}

		// ============== controle activation

		// --- teste le déplacement dans la page
		function checkScroll() {
			let pos = (window.scrollY / (document.body.offsetHeight - window.innerHeight)) * 100;
			// console.log('pos', pos, 'min', posMin, 'max', posMax);
			if (pos >= posMin && pos <= posMax) {
				setTimeout(function() {
					pos = (window.scrollY / (document.body.offsetHeight - window.innerHeight)) * 100;
					if (pos >= posMin && pos <= posMax) {
						showPopup();
						document.removeEventListener('scroll', checkScroll);
					};
				}, 50);
			};
		};

		// --- teste la position d'un bloc
		function checkPosition() {
			// distance entre le bloc et le haut du viewport
			let posTop = document.querySelector(defaults.activationValue).getBoundingClientRect().top;
			// si le bloc est en haut 
			// ou le bloc est visible en etant au bas de la page
			if ((posTop > (defaults.scrollOffsetTop * -1) && posTop < defaults.scrollOffsetBottom)
				|| (posTop > 0 && window.scrollY + window.innerHeight == document.body.offsetHeight)) {
				showPopup();
				document.removeEventListener('scroll', checkPosition);
			}
		}

		// ============== INITIALISATION

		function initActivation() {
			if (defaults.activationValue === 0) {
				showPopup();
			} else if (defaults.activationMode === 'time') {
				setTimeout(showPopup, defaults.activationValue); // en ms
			} else if (defaults.activationMode === 'scroll') {
				posMin = defaults.activationValue - defaults.scrollOffsetTop;
				posMax = defaults.activationValue + defaults.scrollOffsetBottom;
				document.addEventListener('scroll', checkScroll);
				document.addEventListener('resize', checkScroll);
			} else {
				document.addEventListener('scroll', checkPosition);
				document.addEventListener('resize', checkPosition);
			}
			// gestion du bouton close
			btnClose.addEventListener('click', hidePopup);
			// fermeture uniquement sur le bouton close ?
			if (!defaults.closeOnlyButton) {
				main.addEventListener('click', function(event) {
					if (event.target.classList.contains('popup-content') == false) {
						hidePopup();
					}
				});
			}
		}

		if (defaults.cookieDuration < 0) {
			// prise en compte d'un changement de l'option de x vers -x
			eraseCookie(defaults.cookieName);
		}

		if (readCookie(defaults.cookieName) === null) {
			initActivation();
			// creation cookie sauf si duree negative
			if (defaults.cookieDuration >= 0) {
				createCookie(defaults.cookieName, true, defaults.cookieDuration);
			}
		}


	})
};