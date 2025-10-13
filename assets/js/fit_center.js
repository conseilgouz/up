/*
 * @LOMART 2017
 * Définir un bloc à la hauteur de son parent (fit_center) ou grand-parent (fit_center_2)
 *
 */

jQuery(document).ready(function($) {
    function fit_center() {
        // centre dans son parent
        $(".fit_center").each(function() {
            var h = $('.fit_center').parent().height();
            $(this).css("padding-top", ((h - $(this).height()) / 2));
            $(this).css("height", h);
        });
        // centre dans son grand-parent
        $(".fit_center_2").each(function() {
            var h = $('.fit_center_2').parent().parent().height();
            $(this).css("padding-top", ((h - $(this).height()) / 2));
            $(this).css("height", h);
        });
    }

    $(window).on("load", fit_center);
    $(window).bind("resize", fit_center);
});
