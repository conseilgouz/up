/* 07-2023 by LOMART */

"use strict";

function upgrid(selector, ratio) {
	document.addEventListener("DOMContentLoaded", function() {

		let bloc = document.querySelector(selector + ' .grid__item');
		let images = document.querySelectorAll(selector + ' figure.upgallery img');

		function resize() {
			let w = bloc.offsetWidth;
			if (w) {
				let h = w * ratio;
				images.forEach(image => { image.style.height = h + 'px'; image.style.width = w + 'px'; })
			}
		}

		window.addEventListener('resize', resize);
		setTimeout(resize, 50);
	});
};