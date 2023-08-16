/* 
 * Search script for critic matic
 */
var template_path = "/wp-content/themes/custom_twentysixteen/template/ajax/";
var critic_search = critic_search || {};

critic_search.debug = false;

jQuery(function ($) {
    $(document).ready(function () {

        addEventListener("popstate", function (e) {
            window.location.reload();
        }, false);

        // Ajax search
        $("body").on("input", ".customsearch_input", function (e) {
            $(".customsearch_input").addClass('loading');

            var keyword = $(this).val();

            if (keyword.length >= 2) {
                $.ajax({
                    type: 'POST',
                    ///context: this,
                    url: window.location.protocol + template_path + "ajax_data.php",
                    data: {"action": "ajax_search", "keyword": keyword, "type": "movie"},
                    success: function (data) {
                        $('.advanced_search_first').html(data).show();
                        $('.advanced_search_menu').show().removeClass("advanced_search_hidden");
                        $(".customsearch_input").removeClass('loading');
                    }
                });
            }
        });

        if (window.location.hash == '#filters') {
            critic_search.menu();
            var w = $('body').width();
            if (w <= 990) {
                $('#search-facets .facet').each(function (i, v) {
                    var id = $(v).attr('id')
                    id = id.replace('facet-', '');
                    if ($("#search-filters li[data-type='" + id + "']").length || $("#search-filters li[data-type='minus-" + id + "']").length) {

                        var scroll_to = '#facet-' + id;
                        var boxTo = $(scroll_to);

                        jQuery('#search-facets').animate({
                            scrollTop: (boxTo.offset().top - 10)
                        }, {
                            queue: false,
                            duration: 1000,
                            complete: function () {
                            }
                        });
                        return false;
                    }

                });

            }
        }

        $('.search-filters-btn').click(function () {
            var w = $('body').width();
            if (w <= 990) {
                if ($("#search-facets").length == 0) {
                    window.location.replace("/search#filters");
                    return false;
                }
                critic_search.menu();
                return false;
            }
        });

        // Close search
        $('.advanced_search_head_close').click(function () {
            $('.advanced_search_first').html('').hide();
            return false;
        });

        $('#spform').each(function () {
            critic_search.init();
            return false;
        });

        $(window).scroll(function () {
            if ($(this).scrollTop() > 300) {
                $('#fiters-btn').addClass('fixed');
            } else {
                $('#fiters-btn').removeClass('fixed');
            }
        });

        critic_search.add_clear_all();
    });
});

critic_search.autocomplite = [];
critic_search.no_submit_filters = [];
critic_search.enable_submit = true;
critic_search.last_submit = 0;
critic_search.uid = 0;

critic_search.in_no_submit = function (id) {
    const index = critic_search.no_submit_filters.indexOf(id);
    if (index > -1) {
        return true;
    }
    return false;
}

critic_search.add_no_submit = function (id) {
    const index = critic_search.no_submit_filters.indexOf(id);
    if (index === -1) {
        critic_search.no_submit_filters.push(id);
    }
}

critic_search.menu = function () {
    var $ = jQuery;
    var c = 'filters-open';
    if ($('body').hasClass(c)) {
        $('body').removeClass(c);
        $('.menu-mask').remove();

    } else {
        $('body').addClass(c);
        $('body').append('<div class="menu-mask"></div>');
        $('body').on('click', '.menu-mask, .close', function () {
            $('body').removeClass(c);
            $('.menu-mask').remove();
            if (window.location.hash == '#filters') {
                history.replaceState(null, null, ' ');
            }
        });
    }
}

critic_search.init_facet = function (v) {
    if (critic_search.debug) {
        console.log('init_facet', v);
    }
    v.find('input[type=checkbox]').click(function () {
        var $this = $(this);
        var type = $this.attr('data-name');
        var id = $this.val();

        //Multi logic
        var parrent = $this.closest('.flex-row');
        if (parrent.hasClass('multi_pm')) {
            var tclass = $this.attr('class');
            parrent.find('input').each(function () {
                var fthis = $(this);
                var fclass = fthis.attr('class');
                if (fclass !== tclass) {
                    var flabel = fthis.closest('label');
                    if (flabel.hasClass('active')) {
                        fthis.prop('checked', false);
                        flabel.removeClass('active');
                        var ftype = fthis.attr('data-name');
                        console.log('remove filter')

                        if (parrent.hasClass('slider')) {
                            var filter = $('#search-filters [data-type="' + type + '"]');
                            if (filter.length) {
                                var fid = filter.attr('id');
                                var fname = fid.replace(type + '-', '');
                                critic_search.remove_filter(type, fname);
                            }
                        } else {
                            critic_search.remove_filter(ftype, id);
                        }
                    }
                }
            });

        }

        if ($this.prop('checked')) {
            var title = $this.attr('data-title');
            var title_pre = $this.attr('data-title-pre');
            var label = $this.closest('label');
            var type_title = label.attr('data-type');
            var ftype = v.attr('data-type');

            critic_search.add_filter(type, id, title, ftype, type_title, title_pre);
            //Plus logic
            /* var plus = $this.hasClass('plus');
             var minus = $this.hasClass('minus');*/

            label.addClass('active');
        } else {
            $this.closest('label').removeClass('active');
            critic_search.remove_filter(type, id);
        }

        if (v.hasClass('ac-holder')) {
            var autocomplite = v.closest('.facet');
            var fid = autocomplite.attr('id');
            if (fid == 'facet-movie' || fid == 'facet-actor') {
                var fcnt = $('#' + fid + ' .facet-ac');
                critic_search.autocomplite[fid] = fcnt;
            }
        }
        critic_search.submit();
    });

    if (typeof search_extend !== 'undefined') {
        search_extend.init_facet(v);
    }
}

critic_search.init = function ($custom_id = '') {
    if (critic_search.debug) {
        console.log('init', $custom_id);
    }
    var $ = jQuery;

    critic_search.uid = 0;
    if ($('body.logged-in #ucnt').length) {
        critic_search.uid = $('#ucnt').attr('data-id');
    }

    // Facets
    $('.facet:not(.init)').each(function (i, v) {
        var v = $(v);
        var id = v.attr('id');

        // Init collapse
        if (!v.hasClass('icllps')) {
            v.addClass('icllps');
            critic_search.init_collapse(id, v);
        }

        if (v.hasClass('collapsed')) {
            // Not init hide facets
            if (id !== $custom_id) {
                return;
            }
        }
        v.addClass('init');

        if (v.hasClass('slider-facet')) {
            var facet_name = id.replace('facet-', '');
            var facet_type = v.attr('data-type');
            critic_search.slider_facet(facet_name, window[facet_name + "_arr"], facet_type);
        }

        critic_search.init_facet(v);

        // more
        critic_search.init_more(v);

        // Facet search
        $("body").on("input", "#" + id + " .autocomplite", function () {
            var v = $(this);
            var keyword = v.val();
            var at = v.attr('ac-type');
            var data_type = v.attr('data-type');
            v.attr('type-kw', keyword);

            if (keyword.length > 2) {
                v.addClass('active');
                if (!v.hasClass('process')) {
                    v.addClass('process');
                    v.attr('submit-kw', keyword);
                    critic_search.submit('autocomplite');
                } else {
                    // Waiting for process                    
                }

            } else {
                if (v.hasClass('active')) {
                    v.removeClass('active');
                    if (at == 'ac') {
                        // ac logic
                        var acholder = v.closest('.facet').find('.ac-holder');
                        acholder.removeClass('active');
                    }
                }
                if (at == 'qf') {
                    //quick find                         
                    if (keyword.length === 0) {
                        critic_search.submit('blockload', '', 'facet-' + data_type);
                    }
                }
            }
            return false;
        });

    });

    $('.facets:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        var id = v.attr('id');

        // Init collapse
        critic_search.init_collapse(id, v);
    });

    // Filters
    $('#search-filters .filter:not(.init)').each(function (i, v) {
        var $this = $(v);
        $this.addClass('init');
        $this.click(function () {
            var $this = $(this);
            if ($this.hasClass('clear-all')) {
                $('#search-filters').remove();
                critic_search.autocomplite = [];
                critic_search.submit();
                return false;
            }
            var type = $this.attr('data-type');
            var id = $this.attr('data-id');

            if (typeof search_extend !== 'undefined') {
                if (critic_search.in_no_submit(type)) {
                    search_extend.remove_filter_no_submit(type, id);
                    return false;
                }
            }

            critic_search.remove_filter(type, id);
            if (type == 'show' || type == 'hide') {
                $('#facet-' + id + ' > .facet-title.wacc').click();
                $('#facets-' + id + ' > .facet-title.wacc').click();
                return false;
            }
            critic_search.autocomplite = [];
            critic_search.submit();
            return false;
        });
    });

    // Sort
    $('#search-sort:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        v.find('a').click(function () {
            var $this = $(this);
            var holder = $this.closest('.nav-tab');
            if (holder.hasClass('active')) {
                var type = $this.attr('data-type');
                if (type === '') {
                    type = $this.attr('data-def');
                }
                var rev_type = 'asc';
                if (type === 'asc') {
                    rev_type = 'desc';
                }
                $this.attr('data-type', rev_type);
            } else {
                $('.sort-wrapper .nav-tab.active').removeClass('active');
                holder.addClass('active');
            }
            critic_search.submit('content');
            return false;
        });
    });

    // Tabs
    $('#search-tabs:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        v.find('a').click(function () {
            var $this = $(this);
            var holder = $this.closest('.nav-tab');
            if (holder.hasClass('active')) {
                return false;
            }
            $('#search-tabs .nav-tab.active').removeClass('active');
            holder.addClass('active');
            $('#search-filters').attr('class', 'tab-' + $this.attr('data-tab'));
            if ($('.filters-wrapper .filter:not(.sohf)').length == 0) {
                $("#search-filters:not(.hide)").addClass('hide');
            }

            critic_search.submit();
            return false;
        });
    });

    // Tabs facet
    $('.facet-tabs:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        v.find('a').click(function () {
            var $this = $(this);
            var holder = $this.closest('.nav-tab');
            if (holder.hasClass('active')) {
                return false;
            }
            var mh = $this.closest('.facet-tabs');
            $(mh).find('.nav-tab.active').removeClass('active');
            holder.addClass('active');
            critic_search.submit('facets');
            return false;
        });
    });

    // Search bar
    $('#sbar:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        v.on("keyup", function () {
            var kv = $(this).val();
            if (kv !== '') {
                $('.sbar .clear:not(.active)').addClass('active');
            } else {
                $('.sbar .clear.active').removeClass('active');
            }
        });

        $("#submit").click(function () {

            critic_search.submit();
            return false;
        });

        $('#spform a.clear').click(function () {
            $(this).removeClass('active');
            $('#sbar').val('');
            critic_search.submit();
            return false;
        });

    });

    // Pager
    $('#pagination:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        v.find("a").click(function () {
            var $this = $(this);
            var holder = $this.closest('li');
            if (holder.hasClass('active')) {
                return false;
            }
            $('.pt-cv-pagination li.active').removeClass('active');
            holder.addClass('active click');
            critic_search.submit('content');
            return false;
        });
    });

    // Rating
    $('#page-content:not(.init)').each(function (i, v) {
        var v = $(v);
        v.addClass('init');
        if (typeof rating !== "undefined") {
            $.each(rating, function (a, b) {
                let rating_content = create_rating_content(b, a, 1);
                if (rating_content) {
                    $('.movie_container[id="' + a + '"]').append(rating_content);
                }
            });
        }
    });

    // Blur
    if (typeof init_spoilers !== "undefined") {
        init_spoilers();
    }

    // Init notes filters
    init_nte();

    if ($('#search-form').hasClass('analytics')) {
        if (typeof search_extend !== 'undefined') {
            search_extend.init();
        }
    }

    // Init related
    if ($('#search-tabs li.active a').attr('data-tab') == 'critics') {
        if (!$('#search-filters li#state-related').length) {
            var count = $('#search-tabs li.active a span.count').text();
            if (count == '(0)') {
                if ($('#facet-state li input[value="related"]').length) {
                    var related = $('#facet-state li input[value="related"]');
                    var fr = related.closest('.flex-row');
                    var text = fr.find('span.cnt').first().text()
                    var articles = 'articles';
                    if (text == '(1)') {
                        articles = 'article';
                    }
                    $('#page-content').html('<div class="msg-content">We found ' + text + ' <a href="#" id="rel_click">related ' + articles + '.</a></div>');
                    $('#rel_click').click(function () {
                        related.click();
                        return false;
                    });
                }
            }
        }
    }
    // Save filters
    critic_search.init_save_filters();
}

critic_search.init_more = function (v) {
    // more
    v.find('.more').click(function () {
        var $this = $(this);
        if ($this.hasClass('active')) {
            $this.removeClass('active');
        } else {
            $('#search-facets .more.active').removeClass('active');
            $this.addClass('active');
        }
        critic_search.submit('facets');
        return false;
    });
}

critic_search.init_collapse = function (id, v) {
    var cc = 'collapsed';
    var facet_id = id.split('-')[1];

    $('#' + id + ' > .facet-title.wacc').click(function () {

        if (v.hasClass(cc)) {
            // Collapsed
            // Load facet data
            v.removeClass(cc);

            if (v.hasClass('defshow')) {
                critic_search.remove_filter('hide', facet_id);
            } else {
                critic_search.add_filter('show', facet_id, facet_id.capitalize(), 'all', '', 'Show ', 'sohf');
            }


            if ($('#' + id + ' > .facet-ch > .blockload').length || $('#' + id + ' > .facets-ch > .blockload').length) {
                critic_search.submit('blockload', '', id);

            } else {
                // Init facet
                critic_search.init(id);
                // Change url
                critic_search.submit('none');
            }

        } else {
            v.addClass('collapsed');

            if (v.hasClass('defshow')) {
                critic_search.add_filter('hide', facet_id, facet_id.capitalize(), 'all', '', 'Hide ', 'sohf');
            } else {
                critic_search.remove_filter('show', facet_id);
            }

            // Change url
            critic_search.submit('none');

        }
        return false;
    });

}

critic_search.slider_facet = function (type, data_arr, ftype = 'all') {
    var $ = jQuery;
    if (critic_search.debug) {
        console.log('slider init: ' + type);
    }
    // Release facet
    if ($("#" + type + "-slider").length === 0) {
        return false;
    }
    var sfrom = document.getElementById(type + '-from');
    var sto = document.getElementById(type + '-to');
    var html5Slider = document.getElementById(type + '-slider');

    var slider = $('#' + type + '-slider');
    var start = parseInt(slider.attr('data-min'));
    var end = parseInt(slider.attr('data-max'));

    var from = parseInt(slider.attr('data-from'));
    if (from === 0) {
        from = start;
    }
    var to = parseInt(slider.attr('data-to'));
    if (to === 0) {
        to = end;
    }

    var custom_type = type;

    if ($('#facet-' + type + ' .include-btns .exc.active').length) {
        custom_type = 'minus-' + type;
    }

    var title_pre = slider.attr('data-title-pre');
    var title_type = slider.attr('data-filter-pre');
    var multipler = parseInt(slider.attr('data-multipler'));
    var shift = parseInt(slider.attr('data-shift'));

    noUiSlider.create(html5Slider, {
        start: [from, to],
        connect: true,
        range: {
            'min': start,
            'max': end
        }
    });

    html5Slider.noUiSlider.on('set.one', function () {
        from = parseInt(sfrom.value);
        to = parseInt(sto.value);
        var extend_title = '';

        if ($('#' + type + '-slider').hasClass('extend')) {
            var fob = $('#' + type + '-from [value="' + from + '"]');
            var tob = $('#' + type + '-to [value="' + to + '"]');
            from = fob.attr('data-value');
            to = tob.attr('data-value');
            extend_title = fob.text() + '-' + tob.text();
        }

        if (from !== from) {
            from = 0;
        }
        if (to !== to) {
            to = 0;
        }

        var filter_type = type;
        var is_minus = false;
        var filter = $('#search-filters [data-type="' + filter_type + '"]');
        if (!filter.length) {
            filter_type = 'minus-' + type;
            is_minus = true;
            // Try to find minus filter
            filter = $('#search-filters [data-type="' + filter_type + '"]');
        }

        var current_type = type;
        var change_type = false;
        if ($('#facet-' + type + ' .include-btns .exc.active').length) {
            current_type = 'minus-' + type;
        }
        if (current_type != custom_type) {
            change_type = true;
            custom_type = current_type;
        }


        //Filter exist?
        if (filter.length) {

            var id = filter.attr('id');

            var ids = id.split('-');
            if (ids[1] == 'use') {
                //remove old filter
                critic_search.remove_filter(filter_type, ids[1]);
            } else {
                var f_from = ids[1];
                var f_to = ids[2];
                if (is_minus) {
                    f_from = ids[2];
                    f_to = ids[3];
                }

                if (f_from == from && f_to == to && !change_type) {
                    //no change, continue
                    return false;
                } else {
                    //remove old filter
                    var key = f_from + '-' + f_to;
                    critic_search.remove_filter(filter_type, key);
                }
            }
        }

        if (from == start && to == end) {
            // no add filter
            return false;
        }

        // Add new filter
        var key = from + '-' + to;
        var title_f = from;
        var title_t = to;

        if (multipler > 0) {
            title_f = title_f / multipler;
            title_t = title_t / multipler;
        }
        if (shift !== 0) {
            title_f = title_f + shift;
            title_t = title_t + shift;
        }
        var title = title_f + '-' + title_t;
        if (extend_title) {
            title = extend_title;
        }



        critic_search.add_filter(custom_type, key, title, ftype, title_type, title_pre);
        critic_search.submit();
    });


    html5Slider.noUiSlider.on('update', function (values, handle) {
        var value = values[handle];
        if (handle) {
            sto.value = Math.round(value);
        } else {
            sfrom.value = Math.round(value);
        }
    });

    sfrom.addEventListener('change', function () {
        html5Slider.noUiSlider.set([this.value, null]);
    });
    sto.addEventListener('change', function () {
        html5Slider.noUiSlider.set([null, this.value]);
    });

    // Canvas
    if (typeof data_arr !== "undefined" && canUseCanvas()) {

        var max_val = 0;
        var max_h = 50;
        for (var i in data_arr) {
            var count = parseInt(data_arr[i]);
            if (max_val < count) {
                max_val = count;
            }
        }

        // Получаем canvas элемент
        let canvas = document.getElementById(type + '-canvas');
        var hw = $(canvas).width();
        var hh = $(canvas).height();
        if (hw < 190) {
            hw = 190;
        }
        canvas.setAttribute('width', hw);
        canvas.setAttribute('height', hh);

        var k = max_h / max_val;
        var kw = hw / Object.keys(data_arr).length;

        let ctx = canvas.getContext('2d');
        ctx.fillStyle = "#f14939";

        var i = 0;
        for (var key in data_arr) {
            var hc = parseInt(parseInt(data_arr[key]) * k);
            var x = i;
            var y = max_h - hc;
            var w = kw * 0.8;
            var w1 = w;
            var x1 = x;
            if (w > 10) {
                w1 = 10;
                x1 = x + (w - w1) / 2;

            }
            var h = hc;
            ctx.fillRect(x1, y, w1, h);
            i += kw;
        }
    }
    $('#facet-' + type + ' .include-btns .btn').click(function () {
        var v = $(this);
        if (!v.hasClass('active')) {
            $('#facet-' + type + ' .include-btns .btn.active').removeClass('active');
            v.addClass('active');
            html5Slider.noUiSlider.set([from, to]);
        }
        return false;
    });
}

critic_search.add_filter = function (type, id, title = '', ftype, type_title = '', title_pre = '', custom_class = '') {
    var $ = jQuery;

    var minus_class = '';
    if (type.indexOf("minus-") !== -1) {
        minus_class = ' fminus';
    }
    var hover_title = type.capitalize();
    if (title != '') {
        hover_title += ' is ' + title;
    }
    if (typeof type_title !== "undefined" && type_title !== '') {
        hover_title = type_title;
        if (title != '') {
            hover_title += ' is ' + title;
        }
    }

    if (custom_class != '') {
        custom_class = ' ' + custom_class;
    }

    var filter = '<li id="' + type + '-' + id + '" class="filter f-' + ftype + minus_class + custom_class + '" data-type="' + type + '" data-id="' + id + '" title="' + hover_title + '" >' + title_pre + title + '<span class="close"></span></li>';

    if ($("#search-filters").length == 0) {
        $('#search-tabs').after('<div id="search-filters"><span>Filters: </span><ul class="filters-wrapper"></ul></div>');
    }

    if ($(".filters-wrapper .clear-all").length !== 0) {
        $(".filters-wrapper .clear-all").before(filter);
    } else if ($(".filters-wrapper .save-filters").length !== 0) {
        $(".filters-wrapper .save-filters").before(filter);
    } else {
        $('.filters-wrapper').append(filter);
    }
    critic_search.add_clear_all();

    if ($('.filters-wrapper .filter:not(.sohf)').length > 0) {
        $("#search-filters.hide").removeClass('hide');
    } else {
        $("#search-filters:not(.hide)").addClass('hide');
}
}

critic_search.add_clear_all = function () {

    var clear_url = '/search';
    if (typeof search_extend !== 'undefined') {
        clear_url = '/analytics';
    }

    // Tab
    var tab = $('#search-tabs .active a').attr('data-id');
    if (tab !== '') {
        clear_url = clear_url + '/tab_' + tab
    }

    critic_search.init_save_filters();
    
    var filter_len = $('.filters-wrapper .filter:not(.sohf)').length;
    if (filter_len >= 3 && !$(".filters-wrapper .clear-all").length) {
        $('.filters-wrapper .save-filters').before('<li class="filter clear-all" title="Clear filters"><a href="' + clear_url + '">Clear <span class="close"></span></a></li>');
    }
}

critic_search.remove_filter = function (type, id) {

    var $ = jQuery;
    $('#search-filters #' + type + '-' + id).remove();


    var filter_len = $('.filters-wrapper .filter:not(.sohf)').length;

    if (filter_len == 0) {
        $("#search-filters:not(.hide)").addClass('hide');
        $("#search-filters .save-filters").remove();
    }

    if (filter_len <= 3) {
        $("#search-filters .clear-all").remove();
    }

    if ($('.filters-wrapper .filter').length === 0) {
        $('#search-filters').remove();
    }
}

critic_search.init_save_filters = function () {
    var $ = jQuery;
    
    var filter_len = $('.filters-wrapper .filter:not(.sohf)').length;
    if (filter_len > 0 && !$(".filters-wrapper .save-filters").length) {
        // Append save
        $('.filters-wrapper').append('<li class="filter save-filters sohf" title="Save filter">Save <i class="icon icon-star"></i></li>');
    }

    var save_filter = $(".filters-wrapper .save-filters");

    if (save_filter.length) {
        var data_uf = 0;
        var edit = false;

        if ($('#search-url').length > 0) {
            data_uf = $('#search-url').attr('data-uf');
        }
        if (data_uf > 0) {
            edit = true;
        }

        if (!edit && save_filter.hasClass('edit')) {
            save_filter.removeClass('edit');
            save_filter.attr('title', 'Save filter');
            save_filter.html('Save <i class="icon icon-star"></i>');

        } else if (edit && !save_filter.hasClass('edit')) {
            save_filter.addClass('edit');
            save_filter.attr('title', 'Edit filter');
            save_filter.html('Edit <i class="icon icon-star"></i>');
        }

    }

    $(".filters-wrapper .save-filters:not(.init)").each(function () {
        var $this = $(this);
        $this.addClass('init');
        $this.click(function () {
            if (critic_search.uid == 0) {
                return false;
            }
            if (typeof add_popup !== "undefined") {
                add_popup();
                var filter_title = $(".filters-wrapper .save-filters").attr('title');
                var content = '<div class="default_popup"><h2>' + filter_title + '</h2><div id="link_form"><div class="loading"><i class="icon icon-loader"></i></div><div class="form"></div></div></div>';
                jQuery('.popup-content').html(content);
                jQuery('input[id="action-popup"]').click();

                var data = {
                    'link_form': 1,
                    'wpapi': 1,
                    'url': window.location.pathname,
                }
                critic_search.ajax(data, function (rtn) {
                    $('#link_form').addClass('load');
                    $('#link_form .form').html(rtn);
                    // Add title
                    var filter_placeholder = '';
                    $('.filters-wrapper .filter.init:not(.sohf)').each(function () {
                        var title = $(this).attr('title');

                        if (filter_placeholder == '') {
                            filter_placeholder = title;
                        } else {
                            filter_placeholder += ' and ' + title;
                        }
                    });
                    $('.col_input .title').attr('placeholder', filter_placeholder).focus();
                    $('#submit-filter').click(function () {
                        var title = $('#link_form .col_input .title').val();
                        if (!title) {
                            title = $('#link_form .col_input .title').attr('placeholder');
                        }
                        var publish = 0;
                        if ($('#link_form input#publish').prop('checked')) {
                            publish = 1;
                        }
                        var send_data = {
                            'wpapi': 1,
                            'link_form': 1,
                            'submit': 1,
                            'link': $('#link_form .col_input .link').val(),
                            'title': title,
                            'content': $('#link_form .col_input .content').val(),
                            'publish': publish,
                        }

                        $('#link_form').removeClass('load');
                        critic_search.ajax(send_data, function (rtn) {
                            $('#link_form').addClass('load');
                            $('#link_form .form').html(rtn);
                            if ($('#link_form .form .success').length) {
                                $('#search-url').attr('data-uf', 1);
                                critic_search.init_save_filters();
                            }
                        });
                        return false;
                    })
                });
            }
            return false;
        });
    });
}

critic_search.update_facets = function ($rtn = [], $holder = '#facets', $is_child = false) {
    if (critic_search.debug) {
        console.log('update facets', $rtn, $holder, $is_child);
    }
    var new_ids = [];
    $rtn.find($holder + ' > .ajload').each(function (i, v) {
        var v = $(v), id = v.attr('id');
        new_ids.push(id);
        if ($($holder + "> #" + id).length !== 0) {
            // remove old exist facet
            $($holder + " > #" + id).remove();
        }

        // add
        $($holder).append(v);

        // autocomplite
        v.find('.facet-ac').each(function () {
            if (critic_search.autocomplite[id]) {
                $('#' + id + ' .facet-ac').html(critic_search.autocomplite[id]);
                $('#' + id + ' input.autocomplite').addClass('active');
            }
        })

        if (v.hasClass('facets')) {
            critic_search.update_facets($($holder), '#' + id + ' .facets-ch', true);
        }
    });

    $rtn.find($holder + ' > .blockload').each(function (i, v) {
        // Hide facets
        var v = $(v), id = v.closest('.facets').attr('id');
        new_ids.push(id);
        $($holder).html(v);
    });


    if (new_ids.length > 0) {
        //remove old facets
        $('#no-facets').remove();
        $($holder + ' > .ajload').each(function (i, v) {
            var v = $(v), id = v.attr('id');
            if (new_ids.indexOf(id) === -1) {
                $($holder + ' > #' + id).remove();
            }
        });
    } else {
        $($holder).html('<p id="no-facets">No available filters found.</p>');
}
}

critic_search.submit = function (inc = '', target = '', facetid = '') {
    if (critic_search.debug) {
        console.log('submit', inc, target);
    }
    if (!critic_search.enable_submit) {
        return false;
    }

    var $ = jQuery;
    var kw = $('#sbar').val();

    var data = {};

    data['inc'] = inc;
    data['uid'] = critic_search.uid;

    critic_search.last_submit = Date.now();
    data['ts'] = critic_search.last_submit;

    // load block logic
    if (inc == 'blockload') {
        data['facetid'] = facetid;
    }

    // kw
    data['search_type'] = 'ajax';
    data['s'] = kw;

    // Analytics
    if ($('#search-form').hasClass('analytics')) {
        data['analytics'] = '1';
        // Check search_extend
        if (typeof search_extend !== 'undefined') {
            search_extend.submit(inc, target);
        }
    }

    // Filters
    $('#search-filters .filter').each(function (i, v) {
        var v = $(v), type = v.attr('data-type'), id = v.attr('data-id');
        if (typeof type !== "undefined") {
            type = type + '[]';
            if (typeof data[type] === "undefined") {
                data[type] = [];
            }
            if (data[type].indexOf(id) === -1) {
                // release logic
                if (id.match(/[0-9]+-[0-9]+/)) {
                    var ids = id.split('-');
                    data[type].push(ids[0]);
                    data[type].push(ids[1]);
                } else {
                    data[type].push(id);
                }
            }
        }
    });

    // Sort
    var sort_active = $('.search-sort .active a');

    if (sort_active.length > 0 && sort_active.attr('data-type') !== '') {
        data['sort'] = sort_active.attr('data-sort') + '-' + sort_active.attr('data-type');
    }

    // Tab
    var tab = $('#search-tabs .active a').attr('data-id');
    if (tab !== '') {
        data['tab'] = tab;
    }

    // Page
    $('#pagination li.active.click a').each(function (i, v) {
        var v = $(v), p = v.attr('data-id');
        data['p'] = p;
    });

    // Expand facet
    $('#facets .more.active').each(function () {
        data['expand'] = $(this).attr('data-id');
    });

    //Cast tab
    $('.facet-tabs li.active:not(.default)').each(function (i, v) {
        var v = $(v);
        var fname = v.closest('.facet-tabs').attr('data-filter');
        data[fname] = v.attr('data-id');
    });


    //Autocomplite facet
    $('.facet .autocomplite').each(function (i, v) {
        var v = $(v);
        var kw = v.val();

        if (inc === 'autocomplite') {
            if (v.hasClass('active')) {
                data['facet_type'] = v.attr('data-type');
                data['facet_keyword'] = kw
                data['facet_count'] = v.attr('data-count');
                data['facet_ac_type'] = v.attr('ac-type');
                return;
            } else {
                v.removeClass('active');
            }
        } else {
            if (kw !== '') {
                // Add facet keywords
                var ac_parent = v.closest('.facet');
                var ac_name = 'ackw-' + ac_parent.attr('id');
                data[ac_name] = kw;
            }
        }

    });

    critic_search.ajax(data, function (rtn) {

        var ac_facet = '';
        $('.facet .autocomplite.process').each(function (i, v) {
            ac_facet = $(v);
            return;
        });
        var $rtn = $(rtn);

        if (ac_facet) {
            // Check keywords
            var type_kw = ac_facet.attr('type-kw');
            var submit_kw = ac_facet.attr('submit-kw');
            if (type_kw != submit_kw) {
                ac_facet.attr('submit-kw', type_kw);
                critic_search.submit('autocomplite');
                return false;
            }

            ac_facet.removeClass('process');
            var ac_type = ac_facet.attr('ac-type');
            if ($rtn.length !== 0) {
                var facet = ac_facet.closest('.facet');
                if (ac_type == 'ac') {
                    // ac logic
                    var hr = facet.find('.ac-holder').first();
                    hr.html($rtn);
                    hr.addClass('active');
                    critic_search.init_facet(hr);
                } else if (ac_type == 'qf') {
                    // quick filter
                    var holder = facet.find('.facet-ch').first();
                    holder.addClass('custom');
                    holder.html($rtn.find('.facet-ch').html());
                    critic_search.init_facet(holder.closest('.facet'));
                    critic_search.init_more(holder);
                }
            }
            return false;
        }

        var ts = '';
        $rtn.find('#search-ts').each(function (i, v) {
            var v = $(v);
            ts = v.attr('data-id');
        });

        if (critic_search.debug) {
            console.log(ts, critic_search.last_submit);
        }

        if (ts != '' && ts != critic_search.last_submit) {
            return false;
        }

        var inc = '';
        //Url and Title
        $rtn.find('#search-url').each(function (i, v) {
            var v = $(v), id = v.attr('data-id');
            $('#search-url').replaceWith(v);
            history.pushState({path: id}, "", id);
            $('title').html(v.attr('data-title'));
            inc = v.attr('data-inc');
        });

        var up_f = true, up_c = true;

        if (inc == 'content') {
            up_f = false;
        } else if (inc == 'facets') {
            up_c = false;
        } else if (inc == 'none') {
            up_f = false;
            up_c = false;
        } else if (inc == 'blockload') {
            $('#' + facetid).replaceWith($rtn.find('#' + facetid).first());
            up_f = false;
            up_c = false;
        }


        //Facets
        if (up_f) {
            critic_search.update_facets($rtn, '#facets');
            if ($('#search-form').hasClass('analytics')) {
                if (typeof search_extend !== 'undefined') {
                    search_extend.update_facets($rtn);
                }
            }
        }

        //Content
        if (up_c) {
            $rtn.find('#main .ajload').each(function (i, v) {
                var v = $(v), id = v.attr('id');
                if ($("#" + id).length !== 0) {
                    // update
                    $("#" + id).replaceWith(v);
                } else {
                    // add
                }
            });

            if (data['p']) {
                $("html:not(:animated)").animate({scrollTop: 0}, 0);
            }

            $('#search_ajax.not_load').each(function (i, v) {
                var v = $(v);
                v.removeClass('not_load').addClass('loaded');
                load_ajax_block('search_ajax');
            });
        }
        critic_search.init();
    });
}


critic_search.ajax = function (data, cb) {
    if (critic_search.debug) {
        console.log('ajax', data);
    }
    var $ = jQuery;
    return $.ajax({
        type: "POST",
        url: '/wp-content/themes/custom_twentysixteen/template/ajax/search.php',
        data: data,
        success: function (rtn) {
            return cb(rtn);
        },
        error: function (rtn) {
            return cb(rtn);
        }
    });
};

String.prototype.capitalize = function () {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

function canUseCanvas() {
    var elem = document.createElement('canvas');

    if (!!(elem.getContext && elem.getContext('2d'))) {
        // was able or not to get WebP representation
        return true;
    }
    // very old browser like IE 8, canvas not supported
    return false;
}
