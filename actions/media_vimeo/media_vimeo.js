/* Mise en pause de la video si elle n'est pas affichee sur l'ecran
 * from https://github.com/vimeo/player.js
 * and  https://stackoverflow.com/questions/123999/how-can-i-tell-if-a-dom-element-is-visible-in-the-current-viewport
 */
 var player_v={}, iframes_v={};
jQuery(window).ready(function($) { 
    iframes_v = document.querySelectorAll('.up-vimeo-container iframe.play-on-visible');
    for (var i =0;i < iframes_v.length; i++) {
		player_v[i] = new Vimeo.Player(iframes_v[i]);
		player_v[i].on('playing',function (){
			if (!inViewport(iframes_v[i])) { // autostart mais non visible
				player_v[i].pause();
			}
		})
	}
})
jQuery(window).on('resize scroll', function ($) {
    for (var i =0;i < iframes_v.length; i++) {
		if (inViewport(iframes_v[i])) {
			player_v[i].play();
		} else {
			player_v[i].pause();
		}
     }
})
function inViewport(element) {
  var elementBounds = element.getBoundingClientRect();
  return (
      elementBounds.top >= 0 &&
      elementBounds.left >= 0 &&
      elementBounds.bottom <= jQuery(window).height() &&
      elementBounds.right <= jQuery(window).width()
    );
}

