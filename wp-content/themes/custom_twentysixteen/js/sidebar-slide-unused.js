var last_x = 0;
var first_x = 0;
$('#search-facets1').bind('touchmove', function (e) {
    if (!$('body').hasClass('filters-open')) {
        return false;
    }

    //e.preventDefault();
    var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
    var elm = $(this).offset();
    var closemenu = true;

    if ($(e.target).hasClass('noUi-handle') || $(e.target).hasClass('noUi-touch-area')) {
        closemenu = false;
    }
    var x = touch.pageX - elm.left;
    var y = touch.pageY - elm.top;
    if (x < $(this).width() && x > 0 && closemenu) {

        var curr_x = touch.pageX;
        // console.log(touch.pageY + ' ' + curr_x);
        // $('body').css('transform','translate(10%)');
        var position = $('body').position();
        var left = position.left;

        if (last_x == 0) {
            last_x = curr_x;
            first_x = left;
        }
        if (last_x > curr_x) {
            var margin = last_x - curr_x;
            console.log(margin);
            var left_to = left - margin;
            if (left_to <= 0) {
                left_to = 0;
            }
            if (left_to > first_x) {
                left_to = first_x;
            }

            $('body').css({'transform': 'translate(' + left_to + 'px, 0)'});

            if (left + 100 < first_x) {
                var c = 'filters-open';
                $('body').removeClass(c);
                $('.menu-mask').remove();
                $('body').css({'transform': ''});
                last_x = 0;
                first_x = 0;
            }

        }


    }
});

$('#search-facets1').on("click touchend", function (event) {
    $('body').css({'transform': ''});
    last_x = 0;
    first_x = 0;
});