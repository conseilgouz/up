/* 
 * LOMART - init masonry
 */
jQuery(document).ready(function ($) {
var $grid = $(".masonry-grid").masonry({
    itemSelector: "figure",
    columnWidth: ".masonry-grid-sizer",
        percentPosition: true
});
$grid.imagesLoaded().progress(function () {
    $grid.masonry("layout");
    });

if (typeof $grid.infiniteScroll !== 'undefined') { // infinite scroll loaded ?
	var msnry = $grid.data('masonry');
	$grid.infiniteScroll({
		path: getPath,
		append: '.grid__item',
		outlayer: msnry,
	    status: '.page-load-status',
		// debug: true,
	});
	function getPath() {
		currentpage = this.loadCount;
		return '?infinite='+(currentpage+1);
	}
	$grid.on( 'append.infiniteScroll', function( event, body, path, items, response ) {
	// reload photoswipe
		jQuery(document).ready(function($){$("."+event.currentTarget.parentElement.id).jqPhotoSwipe({galleryOpen:function(gallery){}})})
	});	
}
});
