var curent_load = 0;
var last_date = 0;
var template_path = "/wp-content/themes/custom_twentysixteen/template/ajax/";
var site_url = window.location.protocol +"//"+window.location.host;
var analysis_path =window.location.protocol + "/analysis/";


$('body').on('mouseenter', '.main_actors .img_tooltip, .extra_actors .img_tooltip', function () {
    $(this).addClass('relative');
    var img = $(this).find('img').attr('src');
    $(this).append('<div class="Tooltips"><p class="OnTop"><img class="big_img" alt="" src="' + img + '" /></p></div>');
}).on('mouseleave', '.main_actors .img_tooltip, .extra_actors .img_tooltip', function () {
    $(this).removeClass('relative');
    $('.Tooltips').remove();
});

// Load the Visualization API and the corechart package.
google.charts.load('current', {'packages': ['corechart']});


function get_data() {

    var data = new Object();

    data['movie_type'] = $('.date_range_type').val();
    data['movie_genre'] = $('.date_range_genre').val();

    data['animation'] = $('.date_range_animation').val();
    data['graph'] = $('.date_range_graph').val();
    data['inflation'] = $('.date_range_inflation').val();
    data['country'] = $('.date_range_country').val();
    data['start'] = $('.date_range_start').val();
    data['end'] = $('.date_range_end').val();
    data['data_type'] = $('.select_type_block.active').attr('id');

    data['actor_type'] = $('.actos_range_category').val();
    data['movies_limit'] = $('.movies_limit').val();
    data['diversity_select'] = $('.diversity_select').val();

    data['ethnic_display_select'] = $('.display_select').val();

    data['display_select'] = $('a.v_type.is_selected ').attr('id');


    data['country_movie_select'] = $('.country_movie_select').val();
    data['display_xa_axis'] = $('.display_xa_axis').val();
    data['color'] = $('.change_color').attr('id');


    data['filter_director'] = $('.director_select').val();
    data['filter_cast_director'] = $('.cast_director_select').val();
    data['filter_writer'] = $('.writer_select').val();
    data['filter_leed_actor'] = $('.leed_actor_select').val();




    var def_min= $('.budget_min').attr('default-value');
    var budget_min =  $('.budget_min').val();
 if (def_min!=budget_min)
 {
     data['budget_min'] = budget_min;
 }

    var def_max= $('.budget_max').attr('default-value');
    var budget_max =  $('.budget_max').val();
    if (def_max!=budget_max)
    {
        data['budget_max'] = budget_max;
    }



    var ethnycity = new Object();
    var i = 1;
    $('#Ethnycity_container .ethnycity_select').each(function () {

        if (!ethnycity[i]) ethnycity[i] = new Object();
        if ($(this).hasClass('select_disabled')) {
            ethnycity[i][$(this).attr('id')] = 0;
        } else {
            ethnycity[i][$(this).attr('id')] = 1;
        }

        i++;

    });
    data['ethnycity'] = ethnycity;

   /// console.log(JSON.stringify(data));

    return data;
}

var ajax_cast_data_main=0;


function get_ajax_cast_total(topping,type)
{
    return ;

    var data = get_data();

    $.ajax({
        type: "POST",
        url: analysis_path+"get_data.php",

        data: ({
            oper: 'get_movie_cast_data_total',
            'id': topping,
            'type':type,
            data: JSON.stringify(data)
        }),
        success: function (html) {

            $('.footer_table_result .table_ethnic_total').html('<div class="clear_table"></div>'+html);

        },
    });



}




function get_ajax_cast(opened_id) {

return ;

   /// console.log('get_ajax_cast started '+opened_id);
    if (ajax_cast_data_main==1)
    {
        console.log('get_ajax_cast is running');
        return false;
    }


    ajax_cast_data_main=1;
////////////get ajax cast
    $('tr.click_open:visible').each(function () {
        var m_id = $(this).attr('id');
        var big_parent = $(this);
        /// console.log(m_id);
        var child = $(this).find('td.movie_cast_data');

        var data = get_data();


        if (!child.html()) {
            $.ajax({
                type: "POST",
                url: analysis_path+"get_data.php",

                data: ({
                    oper: 'get_movie_cast_data_main',
                    'id': m_id,
                    data: JSON.stringify(data)
                }),
                success: function (html) {
                    child.html(html);
                   /// console.log('get_ajax_cast load complete '+opened_id+' '+m_id);


                    if (Number(opened_id)==Number(m_id))
                    {
                     ///   console.log('get_ajax_cast loaded'+opened_id);

                      var ethnic_buttom =   big_parent.find('.open_ethnic[id="op"]');
                        ethnic_buttom.click();
                        opened_id='';
                    }

                    ajax_cast_data_main=0;
                  get_ajax_cast(opened_id);




                },
                error:function () {
                    ajax_cast_data_main=0;
                    get_ajax_cast(opened_id);
                }
            });

            return false;
        }
        else
        {
            if (Number(opened_id)==Number(m_id))
            {
                ///   console.log('get_ajax_cast loaded'+opened_id);

                var ethnic_buttom =   big_parent.find('.open_ethnic[id="op"]');
                ethnic_buttom.click();
                opened_id='';
            }
        }


    });

}

function get_inner(topping) {


    var whait_html = '<div class="cssload-circle">\n' +
        '\t\t<div class="cssload-up">\n' +
        '\t\t\t\t<div class="cssload-innera"></div>\n' +
        '\t\t</div>\n' +
        '\t\t<div class="cssload-down">\n' +
        '\t\t\t\t<div class="cssload-innerb"></div>\n' +
        '\t\t</div>\n' +
        '</div>';
    $('.footer_table_result').html(whait_html);

    if ($('tr.click_open.opened').html())
    {
        var opened_id= $('tr.click_open.opened').attr('id');
    }


    var data = get_data();

    $.ajax({
        type: "POST",
        url: analysis_path+"get_data.php",

        data: ({
            oper: 'get_inner',
            data: JSON.stringify(data),
            'colum_data': topping
        }),
        success: function (html) {
            $('.footer_table_result').html('<div class="clear_table"></div>'+html).attr('id', topping).addClass('years');


            ajax_cast_data_main=0;
            ///get_ajax_cast_total(topping,'years');
            get_ajax_cast(opened_id);

}
});



}

function loaddata(refrsh) {

    var whait_html = '<div class="cssload-circle">\n' +
        '\t\t<div class="cssload-up">\n' +
        '\t\t\t\t<div class="cssload-innera"></div>\n' +
        '\t\t</div>\n' +
        '\t\t<div class="cssload-down">\n' +
        '\t\t\t\t<div class="cssload-innerb"></div>\n' +
        '\t\t</div>\n' +
        '</div>';

$('div[id="chart_div"]').html(whait_html);



    var data = get_data();
    var request = JSON.stringify(data);

   // console.log(last_date);
   /// console.log(request);

    if ((curent_load == 0 && last_date != request ) || refrsh) {

        last_date = request;

        curent_load = 1;
        $.ajax({
            type: "POST",
            url: analysis_path+"get_data.php",
            data: ({
                oper: 'box',
                data: request
            }),
            success: function (html) {
                $('.chart_script').html(html);
                curent_load = 0;
                if (request != JSON.stringify(get_data())) {
                    loaddata(0);
                }


                //////check open window

                if ($('.footer_table_result').html()) {


                        var m_id= $('.footer_table_result .movie_content').attr('id');
                        if (m_id) {
                            var data = get_data();

                            var whait_html = '<div class="cssload-circle">\n' +
                                '\t\t<div class="cssload-up">\n' +
                                '\t\t\t\t<div class="cssload-innera"></div>\n' +
                                '\t\t</div>\n' +
                                '\t\t<div class="cssload-down">\n' +
                                '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                                '\t\t</div>\n' +
                                '</div>';

                            $('div.footer_table_result').html(whait_html);

                            $.ajax({
                                type: "POST",
                                url: analysis_path+"get_data.php",

                                data: ({
                                    oper: 'movie_data',
                                    id: m_id,

                                    data: JSON.stringify(data),
                                    cat: $('.actos_range_category').val()
                                }),
                                success: function (html) {
                                    $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + m_id + '">' + html + '</div>');
                                    check_trailers(m_id);


                                }
                            });
                        }
                        else {
                            var topping = $('.footer_table_result').attr('id');
                            if (topping) {
                                get_inner(topping);
                            }
                        }






                }

            }
        });
    }
    else
    {
        console.log('is loaded');
    }

}

function check_trailers(m_id) {
    //console.log('check load');

    var id = jQuery('.play_trailer.check_load').attr('id');
if (id)
{

    check_load_trailers(id);
}

}

 function check_load_trailers(id) {

        if (id) {
            var th = jQuery('.play_trailer[id="' + id + '"]');
        }

        jQuery.ajax({
            type: 'POST',
            data: {id: id,'request':'get_trailer'},
            url: site_url + template_path + "get_movie_data.php",
            success: function (html) {

                if (html)
                {
                    th.html('<a href="#" class="button_play_trailer" id="' +html+'">Play Trailer</a>');
                    th.removeClass('check_load').addClass('ready_to_load');

                }


            }
        });
       ////check play button


}

function to_format(num,locale='en') {
    // Nine Zeroes for Billions
    return Math.abs(Number(num)) >= 1.0e+9
        ? Math.round(Math.abs(Number(num)) / 1.0e+9 ) + " B"
        // Six Zeroes for Millions
        : Math.abs(Number(num)) >= 1.0e+6
            ? Math.round(Math.abs(Number(num)) / 1.0e+6 ) + " M"
            // Three Zeroes for Thousands
            : Math.abs(Number(num)) >= 1.0e+3
                ? Math.round(Math.abs(Number(num)) / 1.0e+3 ) + " K"
                : Math.abs(Number(num));
}

function init_slider() {

    $("#main_slider").slider({
        range: true,
        min: Number($('.date_range_start  option:last').attr('value')),
        max: Number($('.date_range_start  option:first').attr('value')),
        values: [Number($('.date_range_start').val()), Number($('.date_range_end').val())],


        create: function () {
            ////  handle.text( $( this ).slider( "value" ) );

            var array = $(this).slider("values");
            $("#custom-handle").text(array[0]);
            $("#custom-handle2").text(array[1]);


        },
        slide: function (event, ui) {


            $("#custom-handle").text(ui.values[0]);
            $("#custom-handle2").text(ui.values[1]);

            $('.date_range_start').val(ui.values[0]).change();
            $('.date_range_end').val(ui.values[1]).change();
            /// console.log( ui.values[ 0 ] + " - $" + ui.values[ 1 ]);

        }


    });



    $("#budget_slider").slider({
        range: true,
        min: Number($('#budget_min').val()),
        max: Number($('#budget_max').val()),
        values: [Number($('#budget_min').val()), Number($('#budget_max').val())],


        create: function () {
            ////  handle.text( $( this ).slider( "value" ) );

            var array = $(this).slider("values");
            $("#budget_custom-handle").text(to_format(array[0]));
            $("#budget_custom-handle2").text(to_format(array[1]));


        },
        slide: function (event, ui) {

            $("#budget_custom-handle").text(to_format(ui.values[0]));
            $("#budget_custom-handle2").text(to_format(ui.values[1]));

            $('#budget_min').val(ui.values[0]).change();
            $('#budget_max').val(ui.values[1]).change();

        }
    });




    $('select.date_range_start').change(function () {
        $("#main_slider").slider({values: [Number($('.date_range_start').val()), Number($('.date_range_end').val())]});
        $("#custom-handle").text($('.date_range_start').val());

    });
    $('select.date_range_end').change(function () {
        $("#main_slider").slider({values: [Number($('.date_range_start').val()), Number($('.date_range_end').val())]});
        $("#custom-handle2").text($('.date_range_end').val());
    });
}


$(document).ready(function () {
    loaddata(1);
    init_slider();


    $('body').on('click', '.data_refresh', function () {

        $('.control_panel').removeClass('visible');
        loaddata(1);
        $('.get_data_refresh').hide();
    });



    $('.date_range').change(function () {

       // loaddata();
        $('.get_data_refresh').show();
    });

    $('select').select2();


    $('body').on('click', '.open_country', function () {

        var prnt = $(this).parents('tr.country_data');

        var op = $(this).attr('id');

        if (op == 'cl') {
            if (prnt.next('tr.country_info').html()) {
                prnt.next('tr.country_info').remove();
            }
            $(this).attr('id', 'op');
        } else {
            $(this).attr('id', 'cl');
            var cinfo = $(this).next('.data').html();


            if (!prnt.next('tr.click_container').html()) {

                var length_col = prnt.find('td').length;
                prnt.after('<tr class="country_info"><td colspan="' + length_col + '"></td></tr>');
            }
            var next_container = prnt.next('tr.country_info');

            var ct_array = JSON.parse(cinfo);
            ///   console.log(ct_array);

            var sub_header = '<tr><th>Country</th><th>Box Office</th></tr>';
            var sub_content = '';

            $.each(ct_array, function (a, b) {

                sub_content += '<tr><td>' + a + '</td><td>$ ' + b + '</td></tr>';

            });


            var result_html = '<table class="tablesorter-blackice">' + sub_header + sub_content + '</table>';


            next_container.find('td').html(result_html);


        }

        return false;
    });


    $('body').on('click', '.open_country_data', function (e) {

       /// console.log('open_country_data');

        var prnt = $(this).parents('tr.click_open');

        var op = $(this).attr('id');

        if (op == 'cl') {

            if (prnt.next('tr.click_container').html()) {
                prnt.next('tr.click_container').remove();
            }

            $(this).attr('id', 'op');

            $('.click_open').removeClass('opened');
        }

        else {



            $(this).attr('id', 'cl');

            var length_col = prnt.find('td').length;

///console.log(length_col);

            prnt.after('<tr class="click_container"><td colspan="' + length_col + '"><div class="cssload-circle">\n' +
                '\t\t<div class="cssload-up">\n' +
                '\t\t\t\t<div class="cssload-innera"></div>\n' +
                '\t\t</div>\n' +
                '\t\t<div class="cssload-down">\n' +
                '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                '\t\t</div>\n' +
                '</div></td></tr>');

            $('.click_open').removeClass('opened');

            prnt.addClass('opened');
            var cntnr_big = prnt.next('tr.click_container');
            cntnr_big.show();
            var cntnr = cntnr_big.find('td');
            var data = get_data();
            var id = prnt.attr('id');

            if (id) {
                $.ajax({
                    type: "POST",
                    url: analysis_path+"get_data.php",
                    data: ({
                        oper: 'get_country_data',
                        id: id,
                        cur_year:$('.cur_year').html(),
                        data:JSON.stringify(data)
                        }),
                    success: function (html) {
                        cntnr.html(html);
                       // console.log('loaded');
                        cntnr_big.show();
                    }
                });
            }
            else {
                cntnr.html('no id');
            }
        }

        return false;
    });




    $('body').on('click', '.open_ethnic', function (e) {

        console.log('open_ethnic');
        var prnt = $(this).parents('tr.click_open');

        var op = $(this).attr('id');

        if (op == 'cl') {
            if (prnt.next('tr.click_container').html()) {
                prnt.next('tr.click_container').remove();
            }
            $(this).attr('id', 'op');


            $('.click_open').removeClass('opened');
        }
 else {
            $(this).attr('id', 'cl');

            var length_col = prnt.find('td').length;

///console.log(length_col);

            prnt.after('<tr class="click_container"><td colspan="' + length_col + '"><div class="cssload-circle">\n' +
                '\t\t<div class="cssload-up">\n' +
                '\t\t\t\t<div class="cssload-innera"></div>\n' +
                '\t\t</div>\n' +
                '\t\t<div class="cssload-down">\n' +
                '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                '\t\t</div>\n' +
                '</div></td></tr>')
            $('.click_open').removeClass('opened');

            prnt.addClass('opened');
            var cntnr_big = prnt.next('tr.click_container');
            cntnr_big.show();
            var cntnr = cntnr_big.find('td');
            var data = get_data();
            var id = prnt.attr('id'),
                inflation = $('.date_range_inflation').val();
            if (id > 0) {
                $.ajax({
                    type: "POST",
                    url: analysis_path+"get_data.php",
                    data: ({
                        oper: 'movie_data',
                        id: id,
                        inflation: inflation,
                        data:JSON.stringify(data),
                        cat: $('.actos_range_category').val()
                    }),
                    success: function (html) {
                        cntnr.html(html);
                        console.log('loaded');
                        cntnr_big.show();

                        check_trailers(id);
                        cntnr.find('.main_ethnic_graph').click();



                    }
                });
            } else {
                cntnr.html('no IMDb id');
            }
        }

    return false;
    });



/*
    jQuery('body').on('click', 'a.actor_info, a.actors_link', function () {
        var actor_id = jQuery(this).attr('data-id');

        add_popup();
        var whait_html = '<div class="cssload-circle">\n' +
            '\t\t<div class="cssload-up">\n' +
            '\t\t\t\t<div class="cssload-innera"></div>\n' +
            '\t\t</div>\n' +
            '\t\t<div class="cssload-down">\n' +
            '\t\t\t\t<div class="cssload-innerb"></div>\n' +
            '\t\t</div>\n' +
            '</div>';
        jQuery('.popup-content').html(whait_html);
        jQuery('input[id="action-popup"]').click();

        var data = get_data();

            $.ajax({
                type: "POST",
                url: analysis_path+"get_data.php",
                data: ({
                    oper: 'get_actordata',
                    id: actor_id,
                    'data':JSON.stringify(data)

                }),
                success: function (html) {
                    jQuery('.popup-content').html('<div class="actor_popup">'+html+'</div>');
                    jQuery('.actor_popup').append('<label for="action-popup" class="popup-close-btn">Close</label>');
                }
            });



        return false;
    });
*/



    $('#Ethnycity_container').sortable({
        placeholder: 'emptySpace',
        update: function (event, ui) {
          //  loaddata();
           $('.get_data_refresh').show();
        }

    });

    $('body').on('click', '.ethnycity_select', function () {

       /// $(this).toggleClass('select_disabled');
        $('.get_data_refresh').show();
        //loaddata();
    });



    $('body').on('click', '.clear_table', function () {

        $('.footer_table_result').html('');

    });




    $('body').on('click', '.change_color', function () {

        var id = $(this).attr('id');

        if (id=='default') {


            $(this).attr('id', 'skin').html('Default color');

        }
        else {

            $(this).attr('id', 'default').html('Skin color');

        }

        $('.get_data_refresh').show();

    });


    $('body').on('click', '.slide_control', function () {

        let prnt = $(this).parents('.control_panel');
        prnt.toggleClass('visible');
    });
});