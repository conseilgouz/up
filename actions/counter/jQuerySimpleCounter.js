// created by marcovincit, modified by LOMART
(function($) {
	$.fn.jQuerySimpleCounter = function( options ) {
	    let settings = $.extend({
            start: 0,
            end: 100,
            easing: 'swing',
            duration: 1500,
            prefix: '', // LM
            suffix: '', // LM
	    complete: ''
	    }, options );

	    const thisElement = $(this);

	    $({count: settings.start}).animate({count: settings.end}, {
            duration: settings.duration,
            easing: settings.easing,
            step: function () {
                let mathCount = Math.ceil(this.count);
                thisElement.html(settings.prefix + mathCount + settings.suffix); // LM
            },
            complete: settings.complete
        });
	};

}(jQuery));
