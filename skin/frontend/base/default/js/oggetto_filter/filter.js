var $ = jQuery;

$(document).ready(function() {

    var onclick = function() {
        var url = $(this).attr('href');
        setLocationAjax(url);
    };
    $(document).on('click', '.toggle-content a', onclick);
    $(document).on('click', '.toolbar a', onclick);

    $(document).find('.toolbar select').attr('onchange', 'setLocationAjax(this.value)');
});

function setLocationAjax(url) {
    var wrapper = $('.col-wrapper'),
        col_left = $('.col-left-first'),
        col_main = $('.col-main'),
        newPath = url.replace(location.host, '').split('//')[1];

    $.ajax({
        url: url,
        data: 'ajax=1',
        dataType: 'json',
        beforeSend: function () {
            wrapper.children().css('opacity', 0.1);
            wrapper.append('<div style="position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2; opacity: 1; background: transparent url(/skin/frontend/rwd/default/images/ajax-loader.gif) no-repeat; background-position:center;"></div>');
        },
        success: function (data) {
            history.replaceState(null, null, newPath);

            wrapper.children().css('opacity', 1);
            wrapper.children().last().remove();

            data.filter = data.filter.replace(/ajax=1/g, '');
            data.products = data.products.replace(/ajax=1/g, '').replace(/setLocation\(/g, 'setLocationAjax(');

            col_left.html(data.filter);
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