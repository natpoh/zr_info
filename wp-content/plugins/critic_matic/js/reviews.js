/*custom select*/
// Iterate over each select element

function listen(box, e)
{
    let cw = 0;

    if (e.touches)
    {
        if (e.touches[0])
        {
            let tch = e.touches[0];
            cw = tch.clientX - 25;
        }

    } else
    {

        cw = e.layerX;
        if (cw < 0)
        {
            let left = box.offsetLeft;
            cw = left + cw;
        }
//console.log('cw',cw);
    }
    let wd = box.clientWidth;
    let rz = 0;
    let inner_blok = box.querySelector(".rating_result_total");

    if (cw > wd)
    {
        cw = wd;
    }

    if (cw) {
        rz = 100 / ((cw / wd) * 5);
    }

    inner_blok.style.width = cw + 'px';
    inner_blok.style.backgroundSize = rz + '%';

}
function listen_click(box, e) {
    let  cw = 0;



    if (e.changedTouches)
    {

        if (e.changedTouches[0])
        {
            let tch = e.changedTouches[0];
            cw = tch.clientX - 25;


        }
        if (!box.classList.contains('totched'))
        {
            box.classList.add("totched");
        }
    } else
    {
        cw = e.layerX;
        if (cw < 0)
        {
            let left = box.offsetLeft;
            cw = left + cw;
        }
    }

    let prnt = box.parentElement;
    let wd = box.clientWidth;

    let rz = (cw / wd) * 5;
    rz = Math.ceil(Number(rz));
    if (rz > 5)
    {
        rz = 5;
    }
    if (rz < 0)
    {
        rz = 0;
    }

    if (box.classList.contains('rating'))
    {
        let rb = 2;

        if (rz >= 2)
        {
            rb = 3;
        }
        if (rz >= 4)
        {
            rb = 1;
        }

        let bs_block = document.querySelector('li.s' + rb);

        bs_block.click();


    }
    //console.log(rz);

    let inner_blok = box.querySelector(".rating_result_total");
    let inner_input = prnt.querySelector('input.wpcr3_frating');
    let number_block = prnt.querySelector(".rating_number_rate");


    if (box.classList.contains('selected') && rz == inner_input.value && !e.changedTouches) {
        inner_input.value = '';
        number_block.textContent = 0;
        inner_blok.style.width = '0px';
        inner_blok.style.backgroundSize = '0%';
        number_block.classList.remove("number_rate_0", "number_rate_1", "number_rate_2", "number_rate_3", "number_rate_4", "number_rate_5");
        number_block.classList.add('number_rate_0');
        box.classList.remove("selected");
        return false;
    }
    number_block.textContent = rz;
    number_block.classList.remove("number_rate_0", "number_rate_1", "number_rate_2", "number_rate_3", "number_rate_4", "number_rate_5");
    number_block.classList.add('number_rate_' + rz);

    inner_input.value = rz;
    box.classList.add("selected");
    let count = rz * 100 / 5;
    let bg = 0;

    if (rz)
    {
        bg = 100 / rz;
    }

    inner_blok.style.width = count + '%';
    inner_blok.style.backgroundSize = bg + '%';
    return false;
}


function init_select() {
    jQuery('select.wpcr3_vote').each(function () {

        // Cache the number of options
        var $this = jQuery(this),
                numberOfOptions = jQuery(this).children('option').length;

        // Hides the select element
        $this.addClass('s-hidden');

        // Wrap the select element in a div
        $this.wrap('<div class="select"></div>');

        // Insert an unordered list after the styled div and also cache the list
        var $list = jQuery('<ul />', {
            'class': 'options'
        }).insertAfter($this);

        // Insert a list item into the unordered list for each select option
        for (var i = 0; i < numberOfOptions; i++) {
            jQuery('<li />', {
                title: $this.children('option').eq(i).text(),
                rel: $this.children('option').eq(i).val(),
                class: $this.children('option').eq(i).attr('class'),
            }).appendTo($list);
        }

        // let vl = 3;
        // $this.val(vl);
        // $list.find('.s' + vl).addClass('selected');

        // Cache the list items
        var $listItems = $list.children('li');

        // Hides the unordered list when a list item is clicked and updates the styled div to show the selected list item
        // Updates the select element to have the value of the equivalent option
        $listItems.click(function (e) {
            e.stopPropagation();
            $this.val(jQuery(this).attr('rel'));
            $listItems.removeClass('selected')
            jQuery(this).addClass('selected');
            /* alert($this.val()); Uncomment this for demonstration! */
        });

    });

    document.querySelectorAll(".rating_input .rating_result").forEach(box => {
        box.addEventListener("mousemove", function (e) {
            if (box.classList.contains('selected')) {
                return false;
            }
            listen(box, e);
            return false;
        });
        box.addEventListener("mouseleave", function (e) {
            if (box.classList.contains('selected')) {
                return false;
            }
            let inner_blok = box.querySelector(".rating_result_total");

            inner_blok.style.width = '0px';
            inner_blok.style.backgroundSize = '0%';
            return false;
        });
        box.addEventListener("click", function (e) {
            if (!box.classList.contains('totched'))
            {
                listen_click(box, e);
            }
        });
        box.addEventListener("touchmove", function (e) {
            listen(box, e);
            return false;
        });
        box.addEventListener("touchend", function (e) {
            listen_click(box, e);
        });
    });
}


/*wpcr3*/
var wpcr3a = wpcr3a || {};
wpcr3a.mousemove_total = 0;
wpcr3a.keypress_total = 0;
wpcr3a.mousemove_need = 5;
wpcr3a.keypress_need = 5;
wpcr3a.captchaResponse = "";

wpcr3a.ajaxPost = function (parent, data, cb) {
    //var host = window.location.host;
    var url = '/service/audience.php';
    //if (host == "zeitgeistreviews.com") {
    //    var url = 'https://service.zeitgeistreviews.com/audience.php';
    //}
    return jQuery.ajax({
        type: "POST",
        url: url,
        data: data,
        dataType: "json",
        success: function (rtn) {
            // console.log(rtn);
            if (rtn.err.length) {
                rtn.err = rtn.err.join('\n');
                alert(rtn.err);
                wpcr3a.enableSubmit();
                return cb(rtn.err);
            }
            if (rtn.needlogin) {
                // load login
                var user_bar = '.site-header-main .user-bar';
                jQuery(user_bar).load(location.href + " " + user_bar);
            }

            return cb(null, rtn);
        },
        error: function (rtn) {
            alert('An unknown error has occurred.');
            wpcr3a.enableSubmit();
        }
    });
};

wpcr3a.submit = function (e) {
    var $ = jQuery;
    var t = $(this);
    var parent = t.closest(".wpcr3_respond_1");
    e.preventDefault();

    var div2 = parent.find('.wpcr3_div_2'), submit = div2.find('.wpcr3_submit_btn');
    var c1 = parent.find('.wpcr3_fconfirm1'), c2 = parent.find('.wpcr3_fconfirm2'), c3 = parent.find('.wpcr3_fconfirm3');

    if (submit.hasClass('wpcr3_disabled')) {
        return false;
    }

    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }

    var fields = div2.find('input,textarea,select');

    var req = [];
    $.each(fields, function (i, v) {
        v = $(v);
        if (v.hasClass('wpcr3_required') && $.trim(v.val()).length === 0) {
            var label = div2.find('label[for="' + v.attr('id') + '"]'), err = '';
            if (label.length) {
                err = $.trim(label.text().replace(':', '')) + ' is required.';
            } else {
                err = 'A required field has not been filled out.';
            }
            req.push(err);
        }
    });

    if (req.length > 0) {
        req = req.join("\n");
        alert(req);
        return false;
    }

    submit.addClass('wpcr3_disabled');
    var postid = parent.attr("data-postid");
    div2.find('.wpcr3_checkid').remove();

    div2.append('<input type="hidden" name="wpcr3_checkid" class="wpcr3_checkid" value="' + postid + '" />');
    div2.append('<input type="hidden" name="wpcr3_ajaxAct" class="wpcr3_checkid" value="form" />');
    fields = div2.find('input,textarea,select');

    var ajaxData = {};
    fields.each(function (i, v) {
        v = $(v), val = v.val();
        if (v.attr('type') === 'checkbox' && v.is(':checked') === false) {
            val = '0';
        }
        ajaxData[v.attr('name')] = val;
    });

    wpcr3a.ajaxPost(parent, ajaxData, function (err, rtn) {
        if (err) {
            return;
        }
        wpcr3a.clearFields();
        min_ofsett = jQuery('.wpcr3_respond_1').offset().top;
        jQuery("html:not(:animated)").animate({scrollTop: min_ofsett}, 200);
        jQuery('.wpcr3_respond_1').html('<h3 class="column_header">Submit your review:</h3><div class="succes_send">Thank you! Your review has been received and will be posted immediately after it passes anti-troll inspection.</div>');
    });
};

wpcr3a.clearFields = function () {
    var $ = jQuery;
    var div2 = $('.wpcr3_div_2'), fields = div2.find('input,textarea');
    wpcr3a.enableSubmit();
    fields.attr('autocomplete', 'off').not('[type="checkbox"], [type="hidden"]').val('');
    $(".wpcr3_frating").val("0");
};

wpcr3a.enableSubmit = function () {
    var $ = jQuery;
    var div2 = $('.wpcr3_div_2'), submit = div2.find('.wpcr3_submit_btn');
    submit.removeClass('wpcr3_disabled');
};

wpcr3a.init = function () {
    var $ = jQuery;
    wpcr3a.showVoteForm();
    init_select();

    var evt_1 = 'mousemove.wpcr3 touchmove.wpcr3';
    $(document).bind(evt_1, function () {
        wpcr3a.mousemove_total++;
        if (wpcr3a.mousemove_total > wpcr3a.mousemove_need) {
            $(document).unbind(evt_1);
        }
    });

    var evt_2 = 'keypress.wpcr3 keydown.wpcr3';
    $(document).bind(evt_2, function () {
        wpcr3a.keypress_total++;
        if (wpcr3a.keypress_total > wpcr3a.keypress_need) {
            $(document).unbind(evt_2);
        }
    });
    $(".wpcr3_respond_2 .wpcr3_div_2:not(.wpc3_open) input, .wpcr3_respond_2 .wpcr3_div_2:not(.wpc3_open)").click(function (e) {
        $(".wpcr3_respond_2 .wpcr3_div_2").addClass('wpc3_open');
    });
    $(".wpcr3_respond_2 .wpcr3_rating_style1_score > div").click(function (e) {
        // debugger;
        var $tr_field = $(this).parent().parent().parent().parent().parent().parent();
        e.preventDefault();
        e.stopImmediatePropagation();

        var wpcr3_rating = $(this).html(), new_w = 20 * wpcr3_rating + "%";
        $tr_field.find(".wpcr3_frating").val(wpcr3_rating);
        $tr_field.find(".wpcr3_rating_style1_base").removeClass('wpcr3_hide');
        $tr_field.find(".wpcr3_rating_style1_average").css("width", new_w);
        $tr_field.find(".wpcr3_rating_style1_status").addClass('wpcr3_hide');

        //   $tr_field.find(".wpcr3_rating_stars").unbind("mouseover.wpcr3").bind("click.wpcr3", wpcr3a.set_hover);

        return false;
    });

    $("input#anon_review").click(function (e) {
        var $this = $(this);
        if ($this.is(":checked")) {
            $('#audience_respond').addClass('anon');            
            $('#wpcr3_fname').removeClass('wpcr3_required');
            $('#wpcr3_femail').removeClass('wpcr3_required');                       
        } else {
            $('#audience_respond').removeClass('anon');
            $('#wpcr3_fname').addClass('wpcr3_required');
            $('#wpcr3_femail').addClass('wpcr3_required');           
        }        
    });

    $("#wp-id_wpcr3_ftext-wrap:not(.init)").each(function () {
        $(this).addClass('.init');
        //Load css and js 
        var css_list = {
            css_dash: '/wp-includes/css/dashicons.min.css',
            css_editor: '/wp-includes/css/editor.min.css'
        }

        add_css_list(css_list);

        tinyMCEPreInit = {
            baseURL: "/wp-includes/js/tinymce",
            suffix: ".min",
            mceInit: {'id_wpcr3_ftext': {
                    theme: "modern", skin: "lightgray", language: "en",
                    formats: {alignleft: [{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
                                styles: {textAlign: "left"}}, {selector: "img,table,dl.wp-caption", classes: "alignleft"}], aligncenter: [{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
                                styles: {textAlign: "center"}}, {selector: "img,table,dl.wp-caption", classes: "aligncenter"}], alignright: [{selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
                                styles: {textAlign: "right"}}, {selector: "img,table,dl.wp-caption", classes: "alignright"}],
                        strikethrough: {inline: "del"}}, relative_urls: false, remove_script_host: false, convert_urls: false, browser_spellcheck: true, fix_list_elements: true, entities: "38,amp,60,lt,62,gt", entity_encoding: "raw", keep_styles: false, cache_suffix: "wp-mce-49110-20201110", resize: "vertical", menubar: false, branding: false, preview_styles: "font-family font-size font-weight font-style text-decoration text-transform", end_container_on_empty_block: true, wpeditimage_html5_captions: true, wp_lang_attr: "en-US", wp_keep_scroll_position: false,
                    wp_shortcut_labels: {"Heading 1": "access1", "Heading 2": "access2", "Heading 3": "access3", "Heading 4": "access4", "Heading 5": "access5", "Heading 6": "access6", "Paragraph": "access7", "Blockquote": "accessQ", "Underline": "metaU", "Strikethrough": "accessD", "Bold": "metaB", "Italic": "metaI", "Code": "accessX", "Align center": "accessC", "Align right": "accessR", "Align left": "accessL", "Justify": "accessJ", "Cut": "metaX", "Copy": "metaC", "Paste": "metaV", "Select all": "metaA", "Undo": "metaZ", "Redo": "metaY", "Bullet list": "accessU", "Numbered list": "accessO", "Insert\/edit image": "accessM", "Insert\/edit link": "metaK", "Remove link": "accessS", "Toolbar Toggle": "accessZ", "Insert Read More tag": "accessT", "Insert Page Break tag": "accessP", "Distraction-free writing mode": "accessW", "Add Media": "accessM", "Keyboard Shortcuts": "accessH"},
                    content_css: "/wp-includes/css/dashicons.min.css?ver=5.7.2,/wp-includes/js/tinymce/skins/wordpress/wp-content.css?ver=5.7.2",
                    plugins: "charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview,image",
                    selector: "#id_wpcr3_ftext", wpautop: true, indent: false,
                    toolbar1: "formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,spellchecker,fullscreen,wp_adv",
                    toolbar2: "strikethrough,hr,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help", toolbar3: "", toolbar4: "",
                    tabfocus_elements: ":prev,:next", body_class: "id_wpcr3_ftext locale-en-us"}},
            qtInit: {},
            ref: {
                plugins: "charmap,hr,lists,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wplink,wpdialogs,wptextpattern,wpview,image",
                theme: "modern",
                language: "en"},
            load_ext: function (url, lang) {
                var sl = tinymce.ScriptLoader;
                sl.markDone(url + '/langs/' + lang + '.js');
                sl.markDone(url + '/langs/' + lang + '_dlg.js');
            }
        };

        var success = function () {
            var init, id, $wrap;
            if (typeof tinymce !== 'undefined') {

                init_wp_editor();

                if (tinymce.Env.ie && tinymce.Env.ie < 11) {
                    tinymce.$('.wp-editor-wrap ').removeClass('tmce-active').addClass('html-active');
                    return;
                }

                for (id in tinyMCEPreInit.mceInit) {
                    init = tinyMCEPreInit.mceInit[id];
                    $wrap = tinymce.$('#wp-' + id + '-wrap');

                    if (($wrap.hasClass('tmce-active') || !tinyMCEPreInit.qtInit.hasOwnProperty(id)) && !init.wp_skip_init) {
                        ///console.log(tinymce);
                        tinymce.init(init);
                        if (!window.wpActiveEditor) {
                            window.wpActiveEditor = id;
                        }
                    }
                }
            }
        };
        var inc = '/wp-includes/js/';
        var third_scripts = {
            jsutils: inc + 'utils.min.js',
            jseditor: '/wp-content/themes/custom_twentysixteen/js/editor.min.js',
            jstiny: inc + 'tinymce/tinymce.min.js',
            jsplugin: inc + 'tinymce/plugins/compat3x/plugin.min.js',
        };
        use_ext_js(success, third_scripts);
    });

    /// $(".wpcr3_respond_2 .wpcr3_rating_stars").bind("mouseover.wpcr3", wpcr3a.onhover);

    var div2 = $('.wpcr3_div_2'), submit = div2.find('.wpcr3_submit_btn');
    submit.click(wpcr3a.submit);

    // wpcr3a.clearFields();
};

wpcr3a.showVoteForm = function () {
    jQuery(".wpcr3_respond_2").show();
    jQuery(".wpcr3_show_btn").addClass('wpcr3_hide');
};

