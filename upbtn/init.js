jQuery(function($) { 
    $(document).ready(function() {
        new $.Zebra_Tooltips($('.Zebra_Tooltips'));
        var zopen = new $.Zebra_Tooltips($('.Zebra_Tooltips_open'));
        zopen.show($('.zebra_tooltips_open'), true); // destroy on close
    });
});
