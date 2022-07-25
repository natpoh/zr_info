jQuery(document).ready(function () {
    //Ajax loading
    jQuery("body").append('<div id="loading">Loading</div>');        
    jQuery(document).ajaxSend(function () {
        jQuery('#loading').show();
    });
    jQuery(document).ajaxComplete(function () {
        jQuery('#loading').hide();
    });

    //search
    var arraymetastring = new Object();
    var array_poviders = new Object();
    var template_path = "/wp-content/themes/custom_twentysixteen/template/ajax/";

    function enable_moble()
    {
        var w = jQuery('body').width();

        if (w <= 768)
        {
            return 1;
        } else
        {
            return 0;
        }

    }

    init_nte();

    jQuery('.mailpoet_paragraph .mailpoet_segment_label').toggle(function () {
        jQuery(this).addClass('mpopened');
        jQuery('.mailpoet_checkbox_label').slideDown(200).css('display', 'block');

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



    jQuery('body:not(.menu-open)').on('click', '.open_menu', function () {

        console.log('open');


        jQuery('body').addClass('menu-open').append('<div class="menu-mask"></div>');

    });


    jQuery('body').on('click', '.menu-mask, .close_header_nav', function () {

        ///  console.log('close');

        jQuery('body').removeClass('menu-open');

        jQuery('.menu-mask').remove();

    });

    jQuery("body").on("click", ".expanf_content", function () {

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
        } else
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

    if (typeof check_load_block == "undefined")
    {
        ///console.log('check_load_block == "undefined"');

        jQuery.ajax({
            type: "GET",
            url: window.location.protocol + "/wp-content/themes/custom_twentysixteen/template/ajax/mailpoet_form.php?id='. $id.'",
            success: function (data) {
                jQuery("#mailpoet_form").html(data);
            }
        });
    }


    jQuery('.play_trailer.check_load').each(function ()
    {
        let th = jQuery(this);

        var id = th.attr('id');



        jQuery.ajax({
            type: 'post',
            data: {id: id, 'request': 'get_trailer'},
            url: window.location.protocol + template_path + "get_movie_data.php",
            success: function (html) {

                if (html)
                {
                    th.html('<a href="#" class="button_play_trailer" id="' + html + '">Play Trailer</a>');
                    th.removeClass('check_load').addClass('ready_to_load');
                } else
                {
                    th.html('');
                }
            }
        });


    });


    jQuery('body').on("click", '.advanced_search_head_close', function () {

        jQuery('.advanced_search_first').html('').hide();
        return false;
    });

    jQuery(document).on("click", function (e) {

        var note = jQuery(".note");
        var div = jQuery(".advanced_search_menu");



        if ((!div.is(e.target) && div.has(e.target).length === 0)) {
            jQuery('.advanced_search_first').html('').hide();
            jQuery('.advanced_search_data').addClass("advanced_search_hidden");
        }


        if ((!note.is(e.target) && note.has(e.target).length === 0))
        {
            jQuery('.note .note_show').hide();
            jQuery('.note').removeClass('togle_show');
        }

    });
    jQuery('body').on('click', '.site_theme_switch', function () {

        if (jQuery('body').hasClass('theme_dark'))
        {
        jQuery('body').removeClass('theme_dark').addClass('theme_white');
            localStorage.setItem('site_theme','theme_white');
        }
        else
        {
         jQuery('body').removeClass('theme_white').addClass('theme_dark');
            localStorage.setItem('site_theme','theme_dark');
        }

        return false;
    });
});

function init_nte() {
    jQuery(function ($) {
        var $body = $('body');
        if (!$body.hasClass('init_nte')) {
            $('body').addClass('init_nte');
            $(document).on("click", function (e) {
                if ($body.hasClass('nte_toggle')) {
                    var nte = $(".nte.open");
                    if ((!nte.is(e.target) && nte.has(e.target).length === 0)) {
                        nte.removeClass('open');
                        $body.removeClass('nte_toggle');
                    }
                } else {
                    //MB init?
                    var $target = $(e.target);
                    /// console.log($target);
                    if ($target.hasClass('btn')) {
                        if ($target.closest('nte:not(.init)')) {
                            init_nte();
                            $target.click();
                        }
                    }
                }
            });
        }

        $('.nte:not(.init)').each(function (i, v) {
            var $this = $(v);
            $this.addClass('init');

            $this.find('.btn').click(function () {

                if ($this.hasClass('open')) {
                    $body.removeClass('nte_toggle');
                    $this.removeClass('open');
                } else {
                    //hide all notes
                    $('.nte.open').removeClass('open');
                    $body.removeClass('nte_toggle');

                    var resize = function () {

                        var note_show = $this.find('.nte_in').first();
                        var nw = note_show.outerWidth();
                        var def_width = note_show.attr('data-width');

                        if (!def_width) {
                            note_show.attr('data-width', nw);
                        } else {
                            nw = parseInt(def_width);
                            note_show.css('width', def_width + 'px');
                        }

                        note_show.css('margin-left', '-' + parseInt(nw / 2) + 'px');
                        var margin_left = parseInt(note_show.css('margin-left').replace('px', ''));
                        var btnm = $this.outerWidth() / 2;
                        var ofl = $this.offset().left;
                        var ww = $(window).width();


                        // Sidebar logic
                        if ($this.closest('#search-facets').length > 0 && ww < 990) {
                            ww = $('#search-facets').outerWidth();                            
                        } 
                        
                        // Popup loigic
                        if ($this.closest('.popup-content').length>0){                            
                             var pw = $('.popup-content').outerWidth();
                             ww = ww-(ww-pw)/2-5;
                        }

                        if (ww < nw) {
                            //Big note
                            nw = parseInt($(window).width() * 0.9);
                            note_show.outerWidth(nw);
                        }

                        var ml = parseInt(btnm + ofl + margin_left);
                        var mr = parseInt(ww - (btnm + ofl + nw + margin_left));

                        if (ml < 0) {
                            note_show.css('margin-left', (margin_left - ml + 5) + 'px');
                        }
                        if (mr < 0) {
                            note_show.css('margin-left', (margin_left + mr - 5) + 'px');
                        }
                    }
                    resize();

                    $(window).resize(function () {
                        $('.nte.open').removeClass('open');
                        $body.removeClass('nte_toggle');                        
                        //resize();
                    });

                    $body.addClass('nte_toggle');
                    $this.addClass('open');
                }
                return false;
            });
        });
    });
}