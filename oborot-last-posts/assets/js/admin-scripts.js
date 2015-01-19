/**
 * Created by emilasp on 19.01.15.
 */
jQuery(function($) {

    $(".oborot-enabled-custom").on("click", function() {

        var checkbox = $(this);
        var inputs = checkbox.parent().parent().find('input[type=text]');

        if( checkbox.is(':checked') )
            inputs.removeClass('disabledField');
        else
            inputs.addClass('disabledField');
    });

});

