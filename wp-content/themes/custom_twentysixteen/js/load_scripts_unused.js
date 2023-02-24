 
function init_spoilers() {
    jQuery('.spoiler_default:not(.init)').each(function () {
        var $this = jQuery(this);
        if (typeof $this.spoilerAlert !== "undefined") {
            $this.addClass('init');
            $this.spoilerAlert({max: 4, partial: 2});
        } else {
            ////try to load
            if (jQuery('body').hasClass('spoilerAlert')) {
                //console.log('spoilerAlert not init');
            } else {
                //console.log('spoilerAlert not load');
                var third_scripts = {
                    spoilerAlert: '/wp-content/themes/custom_twentysixteen/js/spoiler.min.js'
                };
                use_ext_js(init_spoilers, third_scripts);
            }
        }
    });
}

function init_gallery() {
    jQuery('.su-custom-gallery:not(.ginit)').each(function () {
        jQuery(this).addClass('ginit');

        if (typeof init_gallereies_shortcodes !== "undefined") {
            init_gallereies_shortcodes();
        } else {
            //Load css and js 
            var plugin = '/wp-content/plugins/shortcodes-ultimate/assets';
            var css_list = {
                magnific_popup_css: plugin + '/css/magnific-popup.css',
                galleries_shortcodes_css: plugin + '/css/galleries-shortcodes.css'
            }
            add_css_list(css_list);

            var success = function () {
                jQuery('body').addClass('init_gallery');
                init_gallereies_shortcodes();
            }

            var third_scripts = {
                magnific_ajax: plugin + '/js/magnific-ajax.js',
                magnific_popup_js: plugin + '/js/magnific-popup.js',
                swiper_js: plugin + '/js/swiper.js',
                galleries_js: plugin + '/js/galleries-shortcodes-live.js',
            };
            use_ext_js(success, third_scripts);

        }
    });


}
 
 
 
 // jQuery.event.trigger('custom_ajax_load', [jQuery]);
 
function init_short_codes(data_object) {
    return false;
    let $ = jQuery;
    ///spoiler
    //init_spoilers();
    //init_gallery();
    if ($('.short_codes_enabled').length) {

        if ("scripts" in data_object) {
            init_new_scripts(data_object)
        }
        if ("styles" in data_object) {
            init_new_css(data_object["styles"]);
        }

        console.log('short_codes_enabled');
    }
}

function init_new_scripts(data_object) {
    var scripts = data_object["scripts"];
    var scripts_data = '';
    if ("scripts_data" in data_object) {
        // Add script data
        scripts_data = data_object["scripts_data"];
        console.log(scripts_data);
    }

    let $ = jQuery;
    var load_scripts = [];
    var in_page_scripts = [];
    $('script').each(function () {
        var $s = $(this);
        var link = $s.attr('src');
        if (typeof link !== "undefined") {
            load_scripts.push(link)
        } else if ($s.html().length) {
            in_page_scripts.push($s.html().length);
        }
    });

    if (scripts_data) {
        for (let num in scripts_data) {
            var len = scripts_data[num].length;
            if (in_page_scripts.indexOf(len) == -1) {
                try {
                    //eval(scripts_data[num]);     
                    eval.call(windwow,scripts_data[num]);
                    console.log('load', scripts_data[num])
                } catch (e) {
                    console.log('error load', scripts_data[num])
                }
            }

        }
    }

    var third_scripts = {};
    for (let num in scripts) {
        if (load_scripts.indexOf(scripts[num]) == -1) {
            let script_name = ((scripts[num]).match(/\/([^\/]+\.js)/)[1]).replace('.', '-');
            // Load script
            third_scripts[script_name] = scripts[num];
        }
    }
    console.log(third_scripts);
    success = function () {
        console.log('done');
    }

    use_ext_js(success, third_scripts);

}



function init_new_css(css) {
    let $ = jQuery;
    var load_css = [];

    $('link[rel=stylesheet]').each(function () {
        var link = $(this).attr('href');
        if (typeof link !== "undefined") {
            load_css.push(link)
        }
    });

    var third_css = {};
    var exclude_names = ['ie', 'ie7', 'ie8'];
    for (let num in css) {

        if (load_css.indexOf(css[num]) == -1) {
            let script_name = ((css[num]).match(/\/([^\/]+\.css)/)[1]).replace('.', '-');
            if (exclude_names.indexOf(script_name) == -1) {
                third_css[script_name] = css[num];
            }
        }
    }

    console.log(third_css);
    add_css_list(third_css);
}

