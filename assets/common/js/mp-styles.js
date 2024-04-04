(function ($) {
    "use strict";

    $(document).ready(function() {
        
        $('.mpStyles .mp-sms .accordion .accordion-item .accordion-header .accordion-toggle').each(function() {
            let content = $(this).closest('.accordion-header').next('.accordion-content');
            if ($(this).is(':checked')) {
                content.slideDown();
            } else {
                content.slideUp();
            }
        });
        
        $('.mpStyles .mp-sms .accordion .accordion-item .accordion-header .accordion-toggle').change(function() {
            let content = $(this).closest('.accordion-header').next('.accordion-content');
            if ($(this).is(':checked')) {
                content.slideDown();
            } else {
                content.slideUp();
            }
        });

        $('.loader-container').hide();
    });

    var isReloading = false;

    $(window).on('beforeunload', function () {
        isReloading = true;
        $('.loader-container').show();
    });

    $(window).on('load', function () {
        if (!isReloading) {
            $('.loader-container').hide();
        }
        isReloading = false;
    });
    

}(jQuery));