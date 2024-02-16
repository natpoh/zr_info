var watchlist = watchlist || {};


watchlist.click = function() {
    var data = {
        'request': 'get_lists'
    };
    watchlist.ajax(data, function (rtn) {
        console.log(rtn);
    });
}

function watchlist_init() {
    var $ = jQuery;
    $('#add_watchlist:not(.init)').each(function (i, v) {
        console.log()
        var v = $(v);
        v.click(function () {
            var data = {
                'request': 'get_lists'
            };
            watchlist.ajax(data, function (rtn) {
                console.log(rtn);
            });
            return event.preventDefault();
        });
        return false;
    });
}
;

watchlist.ajax = function (data, cb) {
    var $ = jQuery;
    return $.ajax({
        type: "POST",
        url: '/wp-content/themes/custom_twentysixteen/template/ajax/watchlist.php',
        data: data,
        success: function (rtn) {
            return cb(rtn);
        },
        error: function (rtn) {
            return cb(rtn);
        }
    });
};