(function ($){
	// doesn't work without jquery
	if (!$) return;
	// treeView
	function treeView($me) {
		// add treeview class name if not present
		$me.addClass('uptv');
		// collapsable elements i.e. the li with a ul in it
		var $collapse = $me.find('li>ul').parent();

		return {
			//initialize control
			init: function (data) {
				// handle undefined error
				data = data || { };

				// default optoins
				var defaults = {
					expanded: false, // the tree is expanded
					expand: false, // ouvre 1er niveau
                                        delay: 200 // duree effet
				};
				// configuration
				var options = { };
				
				// merge options
				options = $.extend(defaults, data);

				// all the collapsable items which have something
				$collapse.addClass('tv-close');
				// user config
//                                console.log($me)
				if (options.expanded){
					$collapse.addClass('tv-open')
				} else {
					$me.find('ul').css('display', 'none');
				}
				if (options.expand){
                                    $me.find('> li').addClass('tv-open')
                                    $me.find('> li > ul').css('display', 'block')
                                }
				// expand items which have something
				$me.find('.tv-close').on('click', function (event) {
					if ($(event.target).hasClass('tv-close')){
						// expand icon
						$(this).toggleClass('tv-open');
						// the inner list
						var $a = $(this).find('>ul');
						// slide effect
						$a.slideToggle(options.delay);
						// stop propagation of inner elements
						event.stopPropagation();
					}
				});
			},
			// expand all items
			expandAll: function() {
				var items = $me.find('.tv-close');
				items.find('ul').slideDown();
				items.addClass('tv-open');
			},
			// collapse all items
			collapseAll: function() {
				var items = $me.find('.tv-close');
				items.find('ul').slideUp();
				items.removeClass('tv-open');
			}
		}
	}
	// treeView jQuery plugin
	$.fn.treeView = function(options) {
		// if it's a function arguments
		var args = (arguments.length > 1) ? Array.prototype.slice.call(arguments, 1) : undefined;
		// all the elements by selector
		return this.each(function () {
			var instance = new treeView($(this));
			if ( instance[options] ) {
				// has requested method
				return instance[options](args);
			} else {
				// no method requested, so initialize
				instance.init(options);
			}
		});
	}

})(window.jQuery);