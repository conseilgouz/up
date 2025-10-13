(function($) {
    
    function createInitialElements() {
        var $overlay = $('<div />').addClass('rbox-overlay');
        var $lightboxBlock = $('<div />').addClass('rbox-wrap').appendTo($overlay);
        var $lightbox = $('<div />').addClass('rbox').appendTo($lightboxBlock);
        var $content = $('<div />').addClass('rbox-content').appendTo($lightbox);
        var $close = $('<a />').attr({'href': '', 'aria-label': 'Close Button'}).addClass('rbox-close').html("&#x274c;").appendTo($lightbox);
        var $next = $('<a />').attr({'href': '', 'aria-label': 'Next Button'}).addClass('rbox-next').html("&#x25b6;").appendTo($lightbox);
        var $prev = $('<a />').attr({'href': '', 'aria-label': 'Previous Button'}).addClass('rbox-prev').html("&#x25c0;").appendTo($lightbox);
        $('body').append($overlay);
    }

    $.fn.rbox = function(options) {
        if ($('.rbox-overlay').length === 0) {
            createInitialElements();
        }
        options = options || {};
        var that = this;
        $('.rbox-wrap').click(function(e) {
            e.stopPropagation();
        });

        //if series is passed in js options, we need to create the data attr
        if (options.series) {
            this.attr('data-rbox-series', options.series);
        }
        
        $('.rbox-close').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.rbox-overlay').unbind("click");
            
            $(window).off("keyup", closeOnEsc);
            var opts = $('.rbox').data("rboxOpts");
            $('.rbox-overlay').removeClass('rbox-overlay--show');
            $('.rbox-content');

            opts.beforeclose(opts);
            $('.rbox-wrap').removeClass('rbox-wrap--' + opts.type);
            if (opts.animate) {
                $('.rbox-wrap').removeClass(opts.animate);
            }
            $('.rbox-content').html(opts.loading);
            if (opts.scrollTop) {
                $(window).scrollTop(opts.scrollTop);
            }
            opts.onclose(opts);
            //});
            //$('.lightBoxContent').data("rboxOpts", false);
        });

        $('.rbox-next').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var opts = $('.rbox').data("rboxOpts");
            $('.rbox-wrap').removeClass('rbox-wrap--' + opts.type);
            var $thisSeries = that.filter(opts.seriesSelector);
            var index = $thisSeries.index(opts.$anchor);
            if (opts.scrollTop) {
                $(window).scrollTop(opts.scrollTop);
            }
            $thisSeries.eq(index + 1).click();
        });

        $('.rbox-prev').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var opts = $('.rbox').data("rboxOpts");
            $('.rbox-wrap').removeClass('rbox-wrap--' + opts.type);
            var $thisSeries = that.filter(opts.seriesSelector);
            var index = $thisSeries.index(opts.$anchor);
            if (opts.scrollTop) {
                $(window).scrollTop(opts.scrollTop);
            }
            $thisSeries.eq(index - 1).click();
        });        

        
        //get options, giving preference, in order, to data- attributes defined on the html element, options passed when instantiatiing $(element).lightbox(options), defaults.

     
        return this.each(function() {
                namespace = options.namespace || "rbox",
                
                optionTypes = {
                    'strings': [
                        'series',
                        'type',
                        'image',
                        'iframe',
                        'html',
                        'ajax',
                        'video',
                        'videoposter',
                        'caption',
                        'loading',
                        'inline',
                        'bgcustom',
                        'bgcustominner',
                        'closebtnclass',
                        'animate'
                    ],
                    'integers': ['width', 'height'],
                    'floats': [],
                    'arrays':  ['navmarkup'],
                    'objects': [],
                    'booleans': ['fitvids', 'autoplay', 'closeonoverlay', 'closebtn', 'closeonesc']
                    //'functions': ['callback'] FIXME: lets not.
                };

            var $this = $(this),
                dataOptions = $.extend(options, $this.getDataOptions(optionTypes, namespace));
            var opts = $.extend({
                    'series': '', //string, series this lightbox is a part of
                    'type': 'image', //type of content - image, iframe, html or ajax
                    'image': '', //path to image, for type image
                    'iframe': '', //iframe URL
                    'html': '', //arbitrary html
                    'video': '', //Path to video file
                    'inline': '', //selector for element on page whose innerHTML is the content
                    'ajax': '', //URL to fetch ajax content from
                    'caption': '', //optional caption
                    'width': 0,
                    'height': 0,
                    'videoposter': '', //poster image path for video
                    'autoplay': false, //if type==video, if video should autoplay
                    'fitvids': false, //whether to use fitvids plugin (must be included)
                    'namespace': namespace,
                    'loading': 'Loading...',
                    'closeonoverlay': true,
                    'closebtn': true,
                    'closeonesc': true,
                    'navmarkup' : ['&#x274c;', '&#x25c0;', '&#x25b6;'], /*close, prev, next */
                    'closebtnclass' : '',
                    'bgcustom': 'rgba(0, 0, 0, 0.8)', // color value for overlay
                    'bgcustominner' : '#fff', // color value for inner div
                    'animate': '',
                    'beforeopen': function(opts) { return opts; }, //called before open
                    'onopen': function() { $.noop(); }, //called onopen
                    'onclose': function() { $.noop(); }, //called onclose
                    'beforeclose': function() { $.noop(); }
                }, dataOptions);


            if (opts.series) {
                opts.seriesSelector = '[data-' + opts.namespace + '-' + 'series=' + opts.series + ']';
            }

            $this.click(function(e) {
                e.preventDefault(); 
                if (opts.closeonesc) {
                   $(window).on("keyup", closeOnEsc);
                }

                if (opts.series) {
                    var $thisSeries = that.filter(opts.seriesSelector);
                    var total = $thisSeries.length;
                    var thisIndex = $thisSeries.index($this) + 1;
                    if (thisIndex >= total) {
                        $('.rbox-next').hide();
                    } else {
                        $('.rbox-next').show();
                    }
                    if (thisIndex == 1) {
                        $('.rbox-prev').hide();
                    } else {
                        $('.rbox-prev').show();
                    }
                } else {
                    $('.rbox-next, .rbox-prev').hide();
                }
                opts.scrollTop = $(window).scrollTop();
              //  $(window).scrollTop(0);  // LM
                opts.$anchor = $this;
                opts = opts.beforeopen(opts);
                getLightBoxContent(opts, showLightbox);
            });

        });

    };

    function closeOnEsc(e) {
        if (e.keyCode === 27) {
            $('.rbox-close').click();
        }
    }

    function getLightBoxContent(opts, callback) {
        switch (opts.type) {
            case "image":
                var src = opts.image;
                var $content = $("<img />").attr("src", src);
                if (opts.width) {
                    $content.attr("width", opts.width);
                }
                if (opts.height) {
                    $content.attr("height", opts.height);
                }
                callback($content, opts);
                break;
            case "iframe":
                var aspectratio = (opts.height / opts.width) * 100;
                var $content = $('<iframe />').attr({"src": opts.iframe}).css({'height': aspectratio + 'vw'});
                if (opts.width) {
                    $content.attr("width", opts.width);
                }
                if (opts.height) {
                    $content.attr("height", opts.height);
                }
                callback($content, opts);
                break;
            case "ajax":
                $('.rbox-content').html(opts.loading);
                $.get(opts.ajax, function(data) {
                    $content = $('<div />').html(data);
                    callback($content, opts);
                });
                break;
            case "html":
                var $content = $('<div />').html(opts.html);
                callback($content, opts);
                break;

            case "inline":
                var $content = $(opts.inline).html();
                callback($content, opts);
                break;

            case "video":
                var $content = $('<video />')
                            .attr("controls", "controls")
                            .addClass("rbox-video-element")
                            .attr("src", opts.video);
                if (opts.autoplay) {
                    $content.attr("autoplay", "autoplay");
                }
                if (opts.videoposter) {
                    $content.attr("poster", opts.videoposter);
                }
                if (opts.width) {
                    $content.attr("width", opts.width);
                }
                if (opts.height) {
                    $content.attr("height", opts.height);
                }
                /*
                opts.beforeclose = function() {
                    var $video = $('.rbox-video-element');
                    $video.get(0).pause();
                    $video.remove();
                };
                */
                callback($content, opts);
                break;
        }
    }

    function showLightbox(content, opts) {

        //var $content = $(content);
        //$(".rbox-wrap").attr('tabindex', '0').focus();
        $('.rbox').data("rboxOpts", opts);
        $('.rbox-wrap').addClass('rbox-wrap--' + opts.type);
        $('.rbox-overlay').addClass('rbox-overlay--show');

        if (opts.animate) {
            $('.rbox-wrap').addClass(opts.animate);
        }

        if (opts.closeonoverlay) {
            $('.rbox-overlay').bind("click", function() {
                $('.rbox-close').click();
            });
        }

        if(opts.bgcustom) {
            $('.rbox-overlay').css({'background': opts.bgcustom});
        }

        if(opts.bgcustominner) {
            $('.rbox-wrap--inline, .rbox-wrap--html, .rbox-wrap--ajax').css({'background': opts.bgcustominner});
        }

        if(!opts.closebtn) {
            $('.rbox-close').css('display', 'none');
        }
        else {
            $('.rbox-close').css('display', '');            
        }

        // if(opts.closebtnmarkup) {
        //     $('.rbox-close').html(opts.closebtnmarkup);
        // }

        if(opts.navmarkup) {
            $('.rbox-close').html(opts.navmarkup[0]);
            $('.rbox-prev').html(opts.navmarkup[1]);
            $('.rbox-next').html(opts.navmarkup[2]);
        }

        if(opts.closebtnclass) {
            $('.rbox-close').addClass(opts.closebtnclass);
        }

        $('.rbox-content').empty().append(content);
        if (opts.caption) {
            var $caption = $('<div />').addClass('rbox-caption').html(opts.caption);
            $('.rbox-content').append($caption);
        }

        if (opts.fitvids) {
            $('.rbox-content').find('iframe').wrap('<div class="rbox-fitvids" />');
            $('.rbox-fitvids').fitVids();    
        }
        opts.onopen(opts);
        $(window).resize(function() {

            if ($(window).height() < $('.rbox').height())
            {            
                $('.rbox-overlay').addClass('rbox-overlay--short');
                $('.rbox-overlay').height($(document).height());
            } else {
                $('.rbox-overlay').removeClass('rbox-overlay--short').css({'height':''});
            }            
        });
        $(window).resize();
    }


    /*
    Get options from data- attributes
        Parameters:                            
            optionTypes: <object>
                example: {
                    'strings': ['option1', 'option2', 'option3'],
                    'integers': ['fooint', 'barint'],
                    'arrays': ['list1', 'list2'],
                    'booleans': ['bool1']
                }

            namespace: <string>
                example: 'pandora'
                    namespace for data- attributes
                
            example html:
                <div id="blah" data-pandora-option1="foobar" data-pandora-fooint="23" data-pandora-list2="apples, oranges" data-pandora-bool1="true">

            usage:
                var dataOptions = $('#blah').getDataOptions(optionTypes, namespace);
    */
    $.fn.getDataOptions = function(optionTypes, namespace) {
        var $element = this;
        var prefix = "data-" + namespace + "-",
            options = {};        
        $.each(optionTypes['strings'], function(i,v) {
            var attr = prefix + v;
            options[v] = $element.hasAttr(attr) ? $element.attr(attr) : undefined;
        });
        $.each(optionTypes['integers'], function(i,v) {
            var attr = prefix + v;
            options[v] = $element.hasAttr(attr) ? parseInt($element.attr(attr)) : undefined;
        });
        $.each(optionTypes['floats'], function(i,v) {
            var attr = prefix + v;
            options[v] = $element.hasAttr(attr) ? parseFloat($element.attr(attr)) : undefined;
        });
        $.each(optionTypes['arrays'], function(i,v) {
            var attr = prefix + v;
            options[v] = $element.hasAttr(attr) ? $.map($element.attr(attr).split(","), $.trim) : undefined;
        }); 
        $.each(optionTypes['booleans'], function(i,v) {
            var attr = prefix + v;
            if ($element.hasAttr(attr)) {
                if ($element.attr(attr) === 'true') {
                    options[v] = true;
                } else if ($element.attr(attr) === 'false') {
                    options[v] = false;
                } else {
                    options[v] = undefined;
                }
            } else {
                options[v] = undefined;
            }
            options[v] = $element.hasAttr(attr) ? $element.attr(attr) === 'true' : undefined;
        });  
        $.each(optionTypes['objects'], function(i,v) {
            var attr = prefix + v;
            options[v] = $element.hasAttr(attr) ? JSON.parse($element.attr(attr)) : undefined;
        });       
        return options;
    }

    /*
    FIXME: possibly improve - http://stackoverflow.com/questions/1318076/jquery-hasattr-checking-to-see-if-there-is-an-attribute-on-an-element#1318091
    */
    $.fn.hasAttr = function(attr) {
        return this.attr(attr) != undefined;
    };


})(jQuery);