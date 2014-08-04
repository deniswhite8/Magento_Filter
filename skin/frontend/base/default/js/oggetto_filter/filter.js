;
jQuery(function ($) {
    "use strict";

    var onclick = function() {
        var url = $(this).attr('href');

        setLocationAjax(url);
    };

    $(document).on('click', '.toggle-content a', onclick);
    $(document).on('click', '.toolbar a', onclick);
    $(document).on('change', '.js-filter-checkbox', function () {
        $(this).parent().children('a').click();
    });

    $(document).find('.toolbar select').attr('onchange', 'setLocationAjax(this.value)');


    var wrapper = $('.col-wrapper'),
        col_left = $('.col-left-first'),
        col_main = $('.col-main');

    if (!wrapper.length) {
        wrapper = $('.main');
    }

    wrapper.append('\
                    <div style="position: absolute;\
                        top: 0;\
                        left: 0;\
                        width: 100%;\
                        height:100%;\
                        z-index:2;\
                        opacity: 1;">\
                            <img src="/skin/frontend/rwd/default/images/ajax-loader.gif" alt="" style="\
                                position: fixed;\
                                top: 50%;\
                                left: 50%;\
                                margin-top: -24px;\
                                margin-left: -24px;\
                            "/>\
                    </div>\
                ');

    wrapper.children().last().hide();

    window.setLocationAjax = function(url) {
        var newPath = url.replace(location.host, '').split('//')[1];

        $.ajax({
            url: url,
            data: 'ajax=1',
            dataType: 'json',
            beforeSend: function () {
                wrapper.children().css('opacity', 0.1);
                wrapper.children().last().show();
                wrapper.children().last().css('opacity', 1);
            },
            success: function (data) {
                history.replaceState(null, null, newPath);

                wrapper.children().css('opacity', 1);
                wrapper.children().last().hide();

                data.filter = data.filter.replace(/ajax=1/g, '');
                data.products = data.products.replace(/ajax=1/g, '').replace(/setLocation\(/g, 'setLocationAjax(');

                col_left.html(data.filter);s
                col_main.html(data.products);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (errorThrown) alert(errorThrown);
            },
            complete: function () {
            }
        });

        event.preventDefault();
        event.stopPropagation();
    }
});