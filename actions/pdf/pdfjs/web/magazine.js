var MagazineView = {
    magazineMode: false,
    oldScale: 1,
    currentPage: 1,
    currentScale: 1,
    layout: 'double',
    maxScale: 2,
    init: function () {
        $('#toolbarViewerRight').prepend('<button id="magazineMode" class="toolbarButton magazineMode hiddenLargeView" title="Switch to Magazine Mode" tabindex="99" data-l10n-id="magazine_mode"><span data-l10n-id="magazine_mode_label">Magazine Mode</span></button>');
        $('#secondaryToolbarButtonContainer').prepend('<button id="secondaryMagazineMode" class="secondaryToolbarButton magazineMode visibleLargeView" title="Switch to Magazine Mode" tabindex="51" data-l10n-id="magazine_mode"><span data-l10n-id="magazine_mode_label">Magazine Mode</span></button>');

        $(document).on('click', '#magazineMode,#exitMagazineView', function (e) {

            if (!MagazineView.magazineMode)
                MagazineView.start();
            else
                MagazineView.destroy();
        });

        $(document).on('click', '#secondaryMagazineMode', function (e) {

            if (!MagazineView.magazineMode)
                MagazineView.start();
            else
                MagazineView.destroy();

            SecondaryToolbar.close();
        });

        $(document).on('click', '.previous-button', function (e) {
            $("#magazine").turn('previous');
        });

        $(document).on('click', '.next-button', function (e) {
            $("#magazine").turn('next');
        });
		
		if(window.location.hash.indexOf('magazineMode=true') > -1)
        {
            parent.document.addEventListener("webviewerloaded", function(){
                PDFViewerApplication.initializedPromise.then(function() {
                    PDFViewerApplication.eventBus.on("pagesloaded", function() {
                        $('#magazineMode').click();
                    })
                })
            })
        }
		if(window.location.hash.indexOf('background=') > -1) { // background color
			ix = window.location.toString().indexOf('background=') + 11;
			col = '#'+window.location.toString().substring(ix);
			$('body').css('background',col);
			$('#toolbarContainer').css('background',col);
			$('#toolbarContainer').css('background','url(../web/images/texture.png), linear-gradient('+col+', #5b5a5a)');
			$('.secondaryToolbar').css('background','url(../web/images/texture.png), linear-gradient('+col+', #5b5a5a)');
			$('.findbar').css('background','url(../web/images/texture.png), linear-gradient('+col+', #5b5a5a)');
		}
    },
	launchMagazineMode: function(e){
	    document.removeEventListener("pagesloaded", MagazineView.launchMagazineMode, true);
		$('#magazineMode').click();
	},
    configureToolbars: function () {
        if (MagazineView.magazineMode) {
            $('.toolbar').hide();
        }
        else {
            $('.toolbar').show();
        }
    },
    start: function () {

        if (PDFViewerApplication.sidebarOpen) 
            document.getElementById('sidebarToggle').click();
        
        MagazineView.magazineMode = true;
        MagazineView.oldViewer = PDFViewerApplication.pdfViewer;
        MagazineView.oldScale = PDFViewerApplication.pdfViewer.currentScale;
        var scale = MagazineView.oldScale;
        PDFViewerApplication.pdfViewer.currentScaleValue = 'page-fit';
		var divWidth = $("#viewerContainer").outerWidth(true);
		var isPhone = (divWidth) < 768;

        MagazineView.currentPage = PDFViewerApplication.page;

        MagazineView.configureToolbars();

        $("#viewerContainer").hide();

        $('#viewerContainer').after('<div class="icon-center-holder"><div class="previous-button" alt="Prev" title="Prev"></div><div class="next-button" alt="Next" title="Sui."></div></div><div class="icon-holder"><div class="zoom-icon zoom-icon-in" alt="Zoom" title="Zoom"></div><div class="exit-icon" id="exitMagazineView" alt="Exit magazine mode" title="Exit magazine mode"></div></div>');
	    if (!isPhone)  {
			$('#viewerContainer').after('<div id="magazineContainer"><div id="magazine"><div ignore="1" class="next-button"></div><div ignore="1" class="previous-button" style="display: block;"></div></div></div>');
		} else {
			$('#viewerContainer').after('<div id="magazineContainer"><div id="magazine"></div></div>');
		}

        $("#magazine").show();

        var pages = [1];
		MagazineView.layout =  isPhone ? 'single' : 'double';
		var turnHeight = (divWidth * 1) / (MagazineView.layout == 'double' ? 2 : 1);
        MagazineView.loadTurnJsPages(pages, $('#magazine'), true, true).then(function () {

            $("#magazine").turn({
                autoCenter: true,
                display: 'single',
                width: divWidth,
                height: turnHeight,
                pages: PDFViewerApplication.pdfDocument.numPages,
                page: 1,
                elevation: 100,
                duration: 600,
                acceleration: !MagazineView.isChrome(),
                when: {
                    missing: function (event, pages) {
                        MagazineView.loadTurnJsPages(pages, this, false, false);
                    },
                    turning: function (event, page, view) {
                        if (!$('#magazine').turn('hasPage', page)) {

                            MagazineView.loadTurnJsPages([page], this, false, true).then(function () {
                                $('#magazine').turn('page', page);
                            });

                            event.preventDefault();
                        }
                        MagazineView.currentPage = page;
                        MagazineView.showHidePageButtons(page);

                    }  
                }
            });

            MagazineView.showHidePageButtons(MagazineView.currentPage);

            setTimeout(function () {
                $("#magazine").turn("display", MagazineView.layout);

                var multiplier = MagazineView.layout == 'double' ? 2 : 1;

                $("#magazine").turn("size",
                    $("#magazine canvas")[0].width * multiplier,
                    $("#magazine canvas")[0].height);

                if (MagazineView.currentPage > 1)
                    $("#magazine").turn("page", MagazineView.currentPage);

                

                $("#magazineContainer").zoom({
                    max: MagazineView.maxScale,
                    flipbook: $('#magazine'),
                    when: {
                        tap: function (event) {

                            if ($(this).zoom('value') == 1) {
                                $('#magazine').
                                    removeClass('animated').
                                    addClass('zoom-in');
                                $(this).zoom('zoomIn', event);
                            } else {
                                $(this).zoom('zoomOut');
                            }
                        },
                        resize: function (event, scale, page, pageElement) {
                            MagazineView.currentScale = scale;
                            arr = $('#magazine').turn('view');
                            if (scale > 1) {
                                arr = [page];
                            }
                            MagazineView.loadTurnJsPages(arr, $('#magazine'), false, false);
                        },
                        zoomIn: function () {
                            $('.zoom-icon').removeClass('zoom-icon-in').addClass('zoom-icon-out');
                            $('#magazine').addClass('zoom-in');
                            MagazineView.resizeViewport();
                        },
                        zoomOut: function () {
                            $('.zoom-icon').removeClass('zoom-icon-out').addClass('zoom-icon-in');
                            setTimeout(function () {
                                $('#magazine').addClass('animated').removeClass('zoom-in');
                                MagazineView.resizeViewport();
                            }, 0);

                        },
                        swipeLeft: function () {
                            $('#magazine').turn('next');
                        },
                        swipeRight: function () {
                            $('#magazine').turn('previous');
                        }
                    }
                });

                $('.zoom-icon').bind('click', function () {
                    if ($(this).hasClass('zoom-icon-in'))
                        $('#magazineContainer').zoom('zoomIn');
                    else if ($(this).hasClass('zoom-icon-out'))
                        $('#magazineContainer').zoom('zoomOut');

                });

            }, 10);
        });
    },
    showHidePageButtons: function (page) {

        $('.previous-button').show();
        $('.previous-button').show();


        if (page == 1)
            $('.previous-button').hide();
        else
            $('.previous-button').show();

        if (page == $('#magazine').turn('pages'))
            $('.next-button').hide();
        else
            $('.next-button').show();
    },
    resizeViewport: function () {

        var width = $(window).width(),
            height = $(window).height(),
            options = $('#magazine').turn('options');

        $('#magazine').removeClass('animated');

        $('#magazineContainer').css({
            width: width,
            height: height - $('.toolbar').height()
        }).zoom('resize');


        if ($('#magazine').turn('zoom') == 2) {
            var bound = MagazineView.calculateBound({
                width: options.width,
                height: options.height,
                boundWidth: Math.min(options.width, width),
                boundHeight: Math.min(options.height, height)
            });

            if (bound.width % 2 !== 0)
                bound.width -= 1;


            if (bound.width != $('#magazine').width() || bound.height != $('#magazine').height()) {

                $('#magazine').turn('size', bound.width, bound.height);

                if ($('#magazine').turn('page') == 1)
                    $('#magazine').turn('peel', 'br');
            }

            $('#magazine').css({ top: -bound.height / 2, left: -bound.width / 2 });
        }

        $('#magazine').addClass('animated');

    },
    calculateBound: function (d) {

        var bound = { width: d.width, height: d.height };

        if (bound.width > d.boundWidth || bound.height > d.boundHeight) {

            var rel = bound.width / bound.height;

            if (d.boundWidth / rel > d.boundHeight && d.boundHeight * rel <= d.boundWidth) {

                bound.width = Math.round(d.boundHeight * rel);
                bound.height = d.boundHeight;

            } else {

                bound.width = d.boundWidth;
                bound.height = Math.round(d.boundWidth / rel);

            }
        }

        return bound;
    },
    cloneCanvas: function (oldCanvas) {
        //create a new canvas
        var newCanvas = document.createElement('canvas');
        var context = newCanvas.getContext('2d');

        //set dimensions
        newCanvas.width = oldCanvas.width;
        newCanvas.height = oldCanvas.height;

        //apply the old canvas to the new one
        context.drawImage(oldCanvas, 0, 0);

        //return the new canvas
        return newCanvas;
    },
    loadTurnJsPages: function (pages, magazine, isInit, defer) {
        var deferred = null;

        if (defer)
            deferred = $.Deferred();

        var pagesRendered = 0;
        for (var i = 0; i < pages.length; i++) {
            if (pages[i] <= 0) continue;
            PDFViewerApplication.pdfDocument.getPage(pages[i]).then(function (page) {

                var destinationCanvas = document.createElement('canvas');
                scale = 1; 
                var unscaledViewport = page.getViewport({scale});
                var divider = MagazineView.layout == 'double' ? 2 : 1;

                scale = Math.min((($('#mainContainer').height() - 20) / unscaledViewport.height), ((($('#mainContainer').width()- 80) / divider) / unscaledViewport.width));

                var viewport = page.getViewport({scale});

                //var viewport = PDFViewerApplication.pdfViewer.getPageView(page.pageIndex).viewport;

                if (MagazineView.currentScale > 1) {
                    scale = MagazineView.currentScale;
                    viewport = page.getViewport({scale});
                }
                destinationCanvas.height = viewport.height; // - ((viewport.height / 100) * 10);
                destinationCanvas.width = viewport.width; // - ((viewport.width / 100) * 10);


                var renderContext = {
                    canvasContext: destinationCanvas.getContext("2d"),
                    viewport: viewport
                };

                page.render(renderContext).promise.then(function () {
                    pagesRendered++;

                    destinationCanvas.setAttribute('data-page-number', page.pageNumber);
                    destinationCanvas.id = 'magCanvas' + page.pageNumber;


                    if (!isInit) {
                        if ($(magazine).turn('hasPage', page.pageNumber)) {

                            var oldCanvas = $('#magCanvas' + page.pageNumber)[0];
                            oldCanvas.width = destinationCanvas.width;
                            oldCanvas.height = destinationCanvas.height;

                            var oldCtx = oldCanvas.getContext("2d");
                            oldCtx.drawImage(destinationCanvas, 0, 0);

                            
                        }
                        else {
                            $(magazine).turn('addPage', $(destinationCanvas), page.pageNumber);
                        }
                    }
                    else {
                        $("#magazine").append($(destinationCanvas));
                    }

                    if (pagesRendered == pages.length)
                        if (deferred)
                            deferred.resolve();
                });
            });
        }

        if (deferred)
            return deferred;

    },
    destroy: function () {
        MagazineView.magazineMode = false;
        MagazineView.currentScale = 1; // reset zoom
        PDFViewerApplication.pdfViewer = MagazineView.oldViewer;
        PDFViewerApplication.currentPageNumber = MagazineView.currentPage;
        PDFViewerApplication.page = MagazineView.currentPage;

        $("#magazineContainer").hide();
        $("#magazineContainer").empty();
		$(".icon-holder").hide();
		$(".icon-holder").empty();
        $("#viewerContainer").show();

        MagazineView.configureToolbars();

    },
    isChrome: function () {
        return navigator.userAgent.indexOf('Chrome') != -1;
    }
};
MagazineView.init();
// enable key left and right
jQuery(window).keydown(function(e){ // Using arrow keys to turn the page
	var previous = 37, next = 39, mult = 106;
	switch (e.keyCode) {
		case previous:
			jQuery('#magazine').turn('previous');
			e.preventDefault();
		break;
		case next:
			jQuery('#magazine').turn('next');
			e.preventDefault();
		break;
	}
});
