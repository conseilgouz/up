(function ($) {
    upcountdown = function (selector) {

        var settings = {
            'date': $(selector).data('date'),
            'zero': $(selector).data('zero')
        }

        function calc() {

            eventDate = Date.parse(settings['date']) / 1000;
            zero = settings['zero'];

            currentDate = Math.floor($.now() / 1000);

            if (eventDate < currentDate) {
                $(selector).find('.elapsed').css('display','');
                $(selector).find('.digits').css('display','none');
                return;
            }

            seconds = eventDate - currentDate;

            years = 0;
            if ($(selector).find(' .years').length > 0) {
                years = Math.floor(seconds / (60 * 60 * 24 * 365));
                seconds -= years * 60 * 60 * 24 * 365;
                $(selector).find('.years').text(years);
            }

            if ($(selector).find(' .months').length > 0) {
                months = Math.floor(seconds / (60 * 60 * 24 * 30.4));
                seconds -= months * 60 * 60 * 24 * 30.4;
                $(selector).find('.months').text(months);
            }

            if ($(selector).find(' .days').length > 0) {
                days = Math.floor(seconds / (60 * 60 * 24));
                seconds -= days * 60 * 60 * 24;
                $(selector).find('.days').text(days);
            }

            if ($(selector).find(' .hours').length > 0) {
                hours = Math.floor(seconds / (60 * 60))+'';
                if (zero) hours = hours.padStart(2,'0');
                seconds -= hours * 60 * 60;
                $(selector).find('.hours').text(hours);
            }

            if ($(selector).find(' .minutes').length > 0) {
                minutes = Math.floor(seconds / 60)+'';
                if (zero) minutes = minutes.padStart(2,'0');
                seconds -= minutes * 60;
                $(selector).find('.minutes').text(minutes);
            }

            if ($(selector).find(' .seconds').length > 0) {
                seconds = Math.floor(seconds) + '';
                if (zero) seconds = seconds.padStart(2,'0');
                $(selector).find('.seconds').text(seconds);
            }
        }

        calc();
        interval = setInterval(calc, 1000);
    }
})(jQuery);


