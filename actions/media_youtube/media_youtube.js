/* Mise en pause de la video si elle n'est pas affichee sur l'ecran
 * from https://developers.google.com/youtube/iframe_api_reference
 * and  https://stackoverflow.com/questions/123999/how-can-i-tell-if-a-dom-element-is-visible-in-the-current-viewport
 */
 var player_y={},iframes_y={}; 
jQuery(window).ready(function($) { 
    iframes_y = document.querySelectorAll('.up-video-container .play-on-visible iframe');
    yt_int = setInterval(function(){ // wait for video to be created
      if(typeof YT === "object"){ 
	      for (var i =0;i < iframes_y.length; i++) {
			player_y[i] = new YT.Player(iframes_y[i].id,{
				events: { 'onReady': onPlayerReady,'onStateChange': onPlayerStateChange }
				});
			}
			clearInterval(yt_int);
		    }	
		
		},500);

    function onPlayerReady(event) {
		if (!inViewport(event.target.getIframe())) { // autostart mais non visible
			event.target.pauseVideo();
		}else {
			event.target.playVideo();
		}
    }
    function onPlayerStateChange(event) {
        switch(event.data) {
          case 0: // video ended
            break;
          case 1: // playing
			if (!inViewport(event.target.getIframe())) { // autostart mais non visible
				event.target.pauseVideo();
			}
            break;
          case 2: // video paused
			break;
        }
      }
})
jQuery(window).on('resize scroll', function ($) {
	for (var i =0;i < iframes_y.length; i++) {
		if (inViewport(iframes_y[i])) {
			player_y[i].playVideo();
		} else {
			player_y[i].pauseVideo();
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

