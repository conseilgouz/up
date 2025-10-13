'use strict';

/*

 Video background

 Author: lemehovskiy
 Website: https://github.com/lemehovskiy

 */

(function ($) {

    $.fn.videoBackground = function (options) {

        var settings = $.extend({
            ratio_x: 16,
            ratio_y: 9,
			pos_x: '50%',
			pos_y: '50%'
        }, options);

        $(this).each(function () {

            var $this = $(this);

            var ratio_x = settings.ratio_x,
                ratio_y = settings.ratio_y,
				pos_x = settings.pos_x,
				pos_y = settings.pos_y,
                video = $this.find('video, iframe');

            var dataOptions = $this.data('video-background');

            if (dataOptions != undefined) {
                if (dataOptions.hasOwnProperty('ratio_x')) {  // LM
                    ratio_x = dataOptions.ratio_x;
                }

                if (dataOptions.hasOwnProperty('ratio_y')) { // LM
                    ratio_y = dataOptions.ratio_y;
                }

               if (dataOptions.hasOwnProperty('pos_x')) {
                    pos_x = dataOptions.pos_x;
                }

               if (dataOptions.hasOwnProperty('pos_y')) {
                    pos_y = dataOptions.pos_y;
                }
				
			}

            $(this).css({
                overflow: 'hidden'
            });

            video.css({
                position: 'absolute',
                left: pos_x,
                top: pos_y,
                transform: 'translate(-'+pos_x+', -'+pos_y+')'
            });

            resize($this, video, ratio_x, ratio_y);

            $(window).on('resize load', function () {
                resize($this, video, ratio_x, ratio_y);
            });
        });

        function resize($this_e, $this_v, ratio_x, ratio_y) {

            var width = $this_e.outerWidth();
            var height = $this_e.outerHeight();

            if (width / height > ratio_x / ratio_y) {
                $this_v.css({
                    "width": width,
                    "height": width / ratio_x * ratio_y
                });
            } else {
                $this_v.css({
                    "width": height / ratio_y * ratio_x,
                    "height": height
                });
            }
        }
    };
})(jQuery);