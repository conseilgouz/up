jQuery(function ($) {
    function scrollToAnchor(hash) {
        var target = $(hash),
                headerHeight = 0, // Get fixed header height
                speed = 500;  // vitesse animation

        target = target.length ? target : $('[name=' + hash.slice(1) + ']');

        if (target.length)
        {
            $('html,body').animate({
                scrollTop: target.offset().top - headerHeight
            }, speed);
            return false;
        }
    }

    if (window.location.hash) {
        scrollToAnchor(window.location.hash);
    }


    $("a[href*=\\#]:not([href=\\#])").click(function ()
    {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '')
                || location.hostname == this.hostname)
        {
            scrollToAnchor(this.hash);
        }
    });
});


