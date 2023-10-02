/*
function check_load_menu()
{


    var pm= jQuery('.primary-menu').width();
    var mt= jQuery('.menu-top-container').width();

 //   console.log(pm,mt);
    if (pm>=mt) {
        jQuery('.primary-menu').css('float','none');

        jQuery('.primary-menu').slick({

            edgeFriction: true,
            infinite: false,
            swipeToSlide: true,
            touchThreshold: 10,
            variableWidth: true,

            slidesToScroll: 1,

            arrows: false,
            accessibility: false,

        });

    }


}
*/

jQuery(document).ready(function () {
   //search
var  arraymetastring = new Object();
var  array_poviders = new Object();
var template_path = "/wp-content/themes/custom_twentysixteen/template/ajax/";
var site_url = window.location.protocol +"//"+window.location.host;


    function enable_moble()
    {
        var w= jQuery('body').width();

        if (w<=768)
        {
            return 1;
        }
        else
        {
            return 0;
        }

    }


    jQuery("body").on("keyup", ".customsearch_input, .customsearch_input_advanced", function (e)
    {

        if(e.which == 13) {
            jQuery(".customsearch_button_advanced").click();
            return false;
        }

        jQuery(".customsearch_input").addClass('loading');

        var keyword = jQuery(this).val();

       if(keyword.length >= 2) {
            jQuery.ajax({
                type: 'POST',
                ///context: this,
                url:site_url+template_path+"ajax_data.php",
                data: { "action": "ajax_search", "keyword": keyword, "type": "movie" },
            success: function (data) {
                // console.log(data);

                jQuery('.advanced_search_first').html(data).show();
                jQuery('.advanced_search_menu').show().removeClass("advanced_search_hidden");
                // jQuery(\'.post-grid .grid-items\').html(data);
                jQuery(".customsearch_input").removeClass('loading');
            }
        });
        }
    });



    jQuery('body').on("click",'.advanced_search_head_close',function () {

        jQuery('.advanced_search_first').html('').hide();
        return false;
    });


    jQuery(document).on("click",function (e) {
        var div = jQuery(".advanced_search_menu");
        var search_buttom = jQuery(".customsearch_component__button");
        var select = jQuery(".select2-selection__choice__remove");


        if ((!div.is(e.target) && div.has(e.target).length === 0)
            && (!search_buttom.is(e.target)  && search_buttom.has(e.target).length === 0)
            && (!select.is(e.target)  && select.has(e.target).length === undefined)
        )
        {

//console.log(e.target.length);

            jQuery('.advanced_search_first').html('').hide();
            div.addClass("advanced_search_hidden");

            if (jQuery('body').width()<=450)
            {
                jQuery('.customsearch_component__button.custom_close').removeClass('custom_close');
            }


        }
    });

    function getsearchdata(page)
    {
        var data = new Object();

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_streaming_services").val()) {
            data["streaming"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_streaming_services").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_genre").val()) {
            data["genre"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_genre").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_release_date").val()) {
            data["release_date"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_release_date").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_cast").val()) {
            data["cast"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_cast").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_director").val()) {
            data["director"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_director").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_movie_rating").val() ) {
            data["movie_rating"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_movie_rating").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_audience_rating").val()) {
            data["audience_rating"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_audience_rating").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_staff_rating").val()) {
            data["staff_rating"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_staff_rating").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_critics").val()) {
            data["critics"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_critics").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_review_publish_date").val()) {
            data["review_publish_date"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_review_publish_date").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_review_category").val()) {
            data["review_category"] = jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_review_category").val();
        }

        if (jQuery(".customsearch_input").val()) {
            data["keyword"] = jQuery(".customsearch_input").val();
        }
        else if (jQuery(".customsearch_input_advanced").val())
        {
            data["keyword"] = jQuery(".customsearch_input_advanced").val();
        }

        if (jQuery(".advanced_search_data:not(.advanced_search_hidden) .advanced_select_review_sort").val()) {
            data["sort_by"] = jQuery(".advanced_select_review_sort").val();
        }
        if (jQuery(".advanced_select_movie_type").val())
        {
            data["movie_type"] = jQuery(".advanced_select_movie_type").val();
        }

        if (jQuery(".advanced_select_movie_pg_rating").val())
        {
            data["movie_pg_rating"] = jQuery(".advanced_select_movie_pg_rating").val();
        }


        data["page"] = page;

        var string = JSON.stringify(data);

        return string;
    }

    function datatolink(data) {
        var result = "";

        if (data) {
            var object = JSON.parse(data);

            jQuery.each(object, function (a, b){

                ////console.log(a, b);

                result += a+"/";

                var br = "";

                if (a == "keyword" || a == "sort_by") {
                    br += "," + b;
                } else {
                    jQuery.each(b, function (c, d) {
                        br += "," + d;
                    });
                }

                if (br) {
                    br = br.substr(1);
                }

                result += br + "/";
            });
        }

        return result;
    }

    jQuery("body").on("click", ".customsearch_component__button, .customsearch_button_advanced,  .advanced_search_head_result, .advanced_searchmovie, .wprss_search_pagination a", function () {


        if (jQuery(this).hasClass('customsearch_component__button'))
        {

            var wb= jQuery('body').width();

            if (wb<=450)
            {

                if (jQuery('.advanced_search_menu').hasClass("advanced_search_hidden"))
                {
                    jQuery('.advanced_search_menu').removeClass("advanced_search_hidden").show();
                    jQuery(this).addClass('custom_close');

                }
                else
                {
                    jQuery('.advanced_search_menu').addClass("advanced_search_hidden");
                    jQuery(this).removeClass('custom_close');


                }
                return;

            }


        }



        var page = jQuery(this).attr("id");

        if (page)
        {
            jQuery("html:not(:animated)").animate({   scrollTop: 0   }, 0);
        }


        if (!page) page = 1;

        if (page == "nextpage") {
            var pgprnt = jQuery(this).parents("ul.pagination");
            pgprntli = pgprnt.find("li.active").next();

            page = pgprntli.find("a").attr("id");
        }

        if (page == "previous") {
            var pgprnt = jQuery(this).parents("ul.pagination");
            pgprntli = pgprnt.find("li.active").prev();

            page = pgprntli.find("a").attr("id");
        }

        pagelink = "page" + page;

        if (page == 1) {
            pagelink="";
        }


        var data = getsearchdata();

        ///console.log(data);


        if (data.length >= 3) {
            ///href="?s&filters="+data;

            ///var hlink = "?s&filters="+data;

            var hlink = window.location.protocol +"/" + datatolink(data) + pagelink;

            //console.log(hlink);

            history.pushState({ path: hlink }, "", hlink);

            jQuery('.advanced_search_ajaxload').show();
            jQuery(".customsearch_input").addClass('loading');
            jQuery(".advanced_search_menu").addClass("advanced_search_hidden");
            jQuery('.customsearch_component__button.custom_close').removeClass('custom_close');
            if (jQuery(".search_grid").html()) {
                var home =1;
            }

          if ( !jQuery(".site-content").hasClass('style_load')) {
              var url = window.location.protocol + "/wp-content/themes/custom_twentysixteen/css/movie_single.css";
              jQuery("<link/>", {
                  rel: "stylesheet",
                  After: "corev4.css",
                  type: "text/css",
                  href: url
              }).appendTo(".site-content");
              jQuery(".site-content").addClass('style_load');
          }


            jQuery.ajax({
                type: "POST",
                context: this,
                url:site_url+template_path+"ajax_data.php",
                data: { "action": "ajax_search", "type": "grid", "filters": data, "home": home, "page": page },
                success: function (data) {
                    // console.log(data);
                    //	console.log(jQuery(".search_grid").html());

                    jQuery('.advanced_search_ajaxload').hide();

                    jQuery(".site-main").html(data);



                    jQuery(".customsearch_input").removeClass("loading");

                    jQuery("img").error(function () {
                        jQuery(this).hide();
                    });
                }
            });
        }
        else if (jQuery(this).attr("href"))
        {
            var link = jQuery(this).attr('href');


            if (link=='#')
            {
                return false;
            }
            window.location = jQuery(this).attr("href");
        }

        return false;
    });

    jQuery("body").on("click",".reset_searchmovie", function () {
        jQuery(".customsearch_input").val("");

        jQuery(".advanced_search_data_block select").each(function () {
            var select = jQuery(this);
            select.val(null).trigger("change");
        });
    });

    function set_request_data(request)
    {
        let data;
        try {
          data =  JSON.parse(request);
        } catch (err) {
            console.log(err);
        }
        if (data)
        {
         // console.log(data);



            jQuery.each(data,function (a,b)
            {
                if (a=='cast' || a=='director')
                {
                    jQuery.each(b,function (i,c)
                    {
                        jQuery('.advanced_search_data_block select.advanced_select_'+a).append('<option value="'+c+'">'+c+'</option>');
                    });
                }


               if (a=='streaming')
               {
                  a= 'streaming_services';
               }
                if (a=='sort_by')
                {
                    a= 'review_sort';
                    b = [b];
                }


                if (jQuery('.advanced_search_data_block select.advanced_select_'+a).attr('class'))
                {
                  //  console.log(b);


                        try {
                            jQuery('.advanced_search_data_block select.advanced_select_'+a).val(b);
                        } catch (err) {
                            console.log(err);
                        }



                }


            });

        }
    }
    jQuery(".customsearch_container__advanced-search-button").click(function () {
        var html = jQuery(".advanced_search_data").html();
        var wb= jQuery('body').width();


        if (!html) {



            var url=window.location.protocol+"/wp-content/plugins/cirtics-review/css/select2.min.css";
            jQuery("<link/>", {
                rel: "stylesheet",
                After: "corev4.css",
                type: "text/css",
                href: url
            }).appendTo(".advanced_search_menu");

            jQuery(".advanced_search_data").html(" ");

            jQuery('.advanced_search_ajaxload').html('<div class="windows8"><div class="wBall" id="wBall_1"><div class="wInnerBall"></div></div><div class="wBall" id="wBall_2"><div class="wInnerBall"></div></div><div class="wBall" id="wBall_3"><div class="wInnerBall"></div></div><div class="wBall" id="wBall_4"><div class="wInnerBall"></div></div><div class="wBall" id="wBall_5"><div class="wInnerBall"></div></div></div>');


            function formatRepo (data) {
                // console.log(repo);

                if (data.text > 0) {
                    var content = "<span class=\"movie-rating\"><div id=\"wpmoly-movie-rating-\" class=\"wpmoly-movie-rating wpmoly-movie-rating-" + data.text + "\" >";

                    for (i = 1; i <= data.text; i++) {
                        content += "<span class=\"wpmolicon icon-star-filled\"></span>";
                    }

                    content += "</div></span>";
                } else {
                    content = data.text;
                }

                return  content;
            }
            function formatRepoSelection (data) {
                var content = data.text,
                    type = content.split(" ")[1],
                    rating = content.split(" ")[2],
                    image_path = "";

                switch(type) {
                    case "hollywood":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/01/02_poop_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "patriotism":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/02/03_PTRT_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "misandry":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/01/04_CNT_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "affirmative":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/01/05_profit_muhammad_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "lgbtq":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/01/06_queer_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "god":
                        image_path =window.location.protocol + "/wp-content/uploads/2017/01/07_cliche_not_brave_" + rating + "_and_0half_out_of_5.png";
                        break;
                    case "vote":
                        if (rating == 3) {
                            image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_green_pay_drk.png";
                        } else if (rating == 2) {
                            image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/01/slider_orange_free.png";
                        } else {
                            image_path = "https://zeitgeistreviews.com/wp-content/uploads/2017/02/slider_red_skip_drk.png";
                        }

                        break;
                }

                return '<span class="movie-a-rating"><img class="nolazy" src="' + image_path + '" /></span>';
            }



            function formatproviders (data) {

                //console.log(data);
                //console.log(array_poviders);

                var id = data.id;
                if (id)
                {
                var imgc = "";

                var img = (array_poviders[id]['i']);
                var alt = (data.text);
                if (img) {
                    imgc = '<span class="data-providers_img"><img class="nolazy" alt="'+alt+'"  src="' + img + '" /></span>';
                }

                }
                var content = "<span class=\"data-providers\">" + imgc + "<span class=\"data-providers_text\">" + data.text + "</span></span>";
                return content;
            }

            function formatcritics (data) {
                //  console.log(repo);

                var imgc = "";
                var img = (arraymetastring[data.text]);

                if (img) {
                    imgc = '<span class="data-critics_img"><img class="nolazy"  src="' + img + '" /></span>';
                }

                var content = "<span class=\"data-critics\">" + imgc + "<span class=\"data-critics_text\">" + data.text + "</span></span>";

                return content;
            }


            jQuery.ajax({
                type: 'POST',
            data: { action: "advanced_search_data" },
                url:site_url+template_path+"advanced_search.php",
            success: function (html) {




                jQuery('.advanced_search_data').html(html);


                var string = jQuery('.critic_data').html();
                jQuery('.critic_data').remove();
                if (string)
                {
                arraymetastring = JSON.parse(string);
                }

                var providers_data = jQuery('.providers_data').html();
                jQuery('.providers_data').remove();
                if (providers_data)
                {
                    array_poviders = JSON.parse(providers_data);
                }

                if (typeof advanced_search_request !== 'undefined') {



                var request = advanced_search_request;//  jQuery('.advanced_search_request').html();
                if (request)
                {
                   // console.log(request);
                    set_request_data(request);
                }

                }


                ///console.log(arraymetastring);


                jQuery.getScript(window.location.protocol+"/wp-content/plugins/cirtics-review/js/select2.min.js").done(function(script, textStatus) {


                    jQuery(".advanced_select_genre").select2();


                    jQuery(".advanced_select_release_date").select2();

                    jQuery(".advanced_select_review_publish_date").select2();
                    jQuery(".advanced_select_review_category").select2();
                    jQuery(".advanced_select_review_sort").select2();

                    jQuery(".advanced_select_movie_pg_rating").select2();

                    jQuery(".advanced_select_movie_type").select2();

                    jQuery(".advanced_select_streaming_services").select2({
                        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                        templateResult: formatproviders,
                        templateSelection: formatproviders
                    });


                    jQuery(".advanced_select_critics").select2({
                        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                        templateResult: formatcritics,
                        templateSelection: formatcritics
                    });
                    jQuery(".advanced_select_movie_rating").select2({
                        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                        templateResult: formatRepo,
                        templateSelection: formatRepo
                    });
                    jQuery(".advanced_select_audience_rating").select2({
                        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                        templateResult: formatRepoSelection,
                        templateSelection: formatRepoSelection
                    });
                    jQuery(".advanced_select_staff_rating").select2({
                        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
                        templateResult: formatRepoSelection,
                        templateSelection: formatRepoSelection
                    });
                    jQuery(".advanced_select_cast").select2({
                        ajax: {
                            url:site_url+template_path+"ajax_data.php",
                            type: 'POST',
                    data: function (params) {
                        var query = {
                            search: params.term,
                            page: params.page || 1,
                            action: "ajax_search",
                            type: "cast"
                        };

                        // Query parameters will be ?search=[term]&page=[page]
                        return query;
                    },
                    processResults: function (data, params) {
                        if (data) {
                            data = JSON.parse(data);
                        }

                        return {
                            results: data.results
                        };
                    }
                }
                });
                    jQuery(".advanced_select_director").select2({
                        ajax: {
                            url:site_url+template_path+"ajax_data.php",
                            type: 'POST',
                            data: function (params) {
                                var query = {
                                    search: params.term,
                                    page: params.page || 1,
                                    action: "ajax_search",
                                    type: "director"
                                };

                                // Query parameters will be ?search=[term]&page=[page]
                                return query;
                            },
                            processResults: function (data, params) {
                                if (data) {
                                    data = JSON.parse(data);
                                }

                                return {
                                    results: data.results
                                };
                            }
                        }
                    });

                    jQuery('.advanced_search_menu').show().removeClass("advanced_search_hidden");
                    jQuery('.advanced_search_data').show().removeClass("advanced_search_hidden");
                    jQuery('.advanced_search_ajaxload').hide();
                    if (wb<=450) {
                        jQuery('.customsearch_component__button:not(".custom_close")').addClass('custom_close');
                    }
                });
            }
        });
        } else {
            if (jQuery('.advanced_search_menu').hasClass("advanced_search_hidden") || jQuery('.advanced_search_data').hasClass("advanced_search_hidden"))
            {
                jQuery('.advanced_search_menu').removeClass("advanced_search_hidden").show();
                jQuery('.advanced_search_data').show().removeClass("advanced_search_hidden");
                if (wb<=450) {
                    jQuery('.customsearch_component__button:not(".custom_close")').addClass('custom_close');
                }
            }

            else
            {
                jQuery('.advanced_search_menu').addClass("advanced_search_hidden");
                jQuery('.advanced_search_data').addClass("advanced_search_hidden");
                if (wb<=450)
                {
                jQuery('.customsearch_component__button.custom_close').removeClass('custom_close');
                }
            }

        }

        return false;
    });





















    jQuery(window).resize(function () {

    ///    check_load_menu();


    });




    jQuery('.mailpoet_paragraph .mailpoet_segment_label').toggle(function () {
        jQuery(this).addClass('mpopened');
        jQuery('.mailpoet_checkbox_label').slideDown(200).css('display','block');

    }, function () {
        jQuery('.mailpoet_checkbox_label').slideUp(200);
        jQuery(this).removeClass('mpopened');
    });


    jQuery('.social_container .search').toggle(function () {

        jQuery('.scont').slideDown(500);

        jQuery('.site-header-main>.search').slideDown(500);

    }, function () {
        jQuery('.scont').slideUp(500);
        jQuery('.site-header-main>.search').slideUp(500);
    });



    jQuery('body:not(.menu-open)').on('click', '.open_menu' ,function () {

        console.log('open');


        jQuery('body').addClass('menu-open').append('<div class="menu-mask"></div>');

       });


    jQuery('body').on('click', '.menu-mask, .close_header_nav' ,function () {

      ///  console.log('close');

        jQuery('body').removeClass('menu-open');

        jQuery('.menu-mask').remove();

    });

    jQuery("body").on("click",".expanf_content", function () {

        var before = jQuery(this).prev('.full_review_content_block.largest');
        before.removeClass('largest');
        jQuery(this).remove();
        return false;
    });



    jQuery('.tranding_open').toggle(function () {

        jQuery('.trendfilters').slideDown(200);

    }, function () {
        jQuery('.trendfilters').slideUp(200);
    });


    jQuery('.togle_bottom').toggle(function () {

        var id = jQuery(this).attr('href');

        id = id.slice(1);


        jQuery('.' + id).slideDown(200);

        return false;

    }, function () {

        var id = jQuery(this).attr('href');
        id = id.slice(1);
        jQuery('.' + id).slideUp(200);

        return false;
    });


    lastct = 0;

    jQuery(window).scroll(function (e) {

        //     var mt = jQuery('#sidebar').offset().top;
        var ct = jQuery(window).scrollTop();
        ///  console.log(ct);



        if (jQuery('.advanced_search_menu').hasClass('advanced_search_hidden')) {

        if (ct > 200) {

            jQuery('.header_nav').addClass('detached');
        } else if (ct == 0) {
            jQuery('.header_nav').removeClass('detached');
        }

        if (ct > lastct + 100) {
            lastct = ct;
            jQuery('.header_nav').addClass('invisible');
        }
        }
        else
        {
            jQuery('.header_nav.invisible').removeClass('invisible');
        }


        if (ct < lastct - 100) {
            lastct = ct;
            jQuery('.header_nav.invisible').removeClass('invisible');
        }


        //  showMenuoffset();
        //   randombg();

    });

        // if ( typeof check_load_block == "undefined" )
        // {
        //     ///console.log('check_load_block == "undefined"');
        //
        //
        //     jQuery.ajax({
        //         type: "GET",
        //         url: window.location.protocol + "/wp-content/themes/custom_twentysixteen/template/ajax/mailpoet_form.php?id="+ id,
        //         success: function (data) {
        //
        //
        //
        //             jQuery("#mailpoet_form").html(data);
        //
        //         }
        //
        //     });
        // }



jQuery('.play_trailer.check_load').each(function ()
    {
        let th = jQuery(this);

        var id =th.attr('id');


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
                else
                {
                    th.html('');
                }


            }
        });


    });


});

