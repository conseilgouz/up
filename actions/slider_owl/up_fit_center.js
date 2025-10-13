/*
 * @LOMART 2017
 * Définir un bloc à la hauteur de son parent (fit_center) ou grand-parent (fit_center_2)
 *
 */

jQuery(document).ready(function($) {
    function fit_center() {
        $(".owl-item > div").each(function() {
            $(this).css("height", 'inherit');
            $(this).css("padding", 0);
            var h = $(this).parent().parent().height();
            $(this).css("padding-top", ((h - $(this).height()) / 2));
            $(this).css("height", h);
        });
    }

    $(window).on("load", fit_center);
    $(window).bind("resize", fit_center);
});
