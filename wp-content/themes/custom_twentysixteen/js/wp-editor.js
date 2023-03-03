
var wp_custom_editor = wp_custom_editor || {};


wp_custom_editor.mce_data = {
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
            tabfocus_elements: ":prev,:next", body_class: "id_wpcr3_ftext locale-en-us"}

wp_custom_editor.load = function (custom_id = '') {
    var $ = jQuery;
    // id_crowd_text id_wpcr3_ftext
    if (typeof tinyMCEPreInit == 'undefined') {
        //Load css and js 
        var css_list = {
            css_dash: '/wp-includes/css/dashicons.min.css',
            css_editor: '/wp-includes/css/editor.min.css'
        }

        add_css_list(css_list);

        tinyMCEPreInit = {
            baseURL: "/wp-includes/js/tinymce",
            suffix: ".min",
            mceInit: {},
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

        tinyMCEPreInit.mceInit[custom_id] = wp_custom_editor.mce_data;

        var success = function () {

            if (typeof tinymce !== 'undefined') {
                init_wp_editor();
                for (let id in tinyMCEPreInit.mceInit) {
                    wp_custom_editor.init_id(id);
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
    } else {
        tinyMCEPreInit.mceInit[custom_id] = wp_custom_editor.mce_data;
        wp_custom_editor.init_id(custom_id);
}
}

wp_custom_editor.init_id = function (custom_id) {

    var $ = jQuery;
    var init, $wrap;
    $wrap = tinymce.$('#wp-' + custom_id + '-wrap');

    if (tinymce.Env.ie && tinymce.Env.ie < 11) {
        tinymce.$($wrap).removeClass('tmce-active').addClass('html-active');
        return false;
    }
    init = tinyMCEPreInit.mceInit[custom_id];
    
    if (($wrap.hasClass('tmce-active') || !tinyMCEPreInit.qtInit.hasOwnProperty(custom_id)) && !init.wp_skip_init) {

        tinymce.init(init);
        tinymce.execCommand('mceRemoveEditor', false, custom_id);
        tinymce.execCommand('mceAddEditor', false, custom_id); 
        
    }
}

wp_custom_editor.init = function () {
    var $ = jQuery;

    jQuery("#wp-crowd-text-wrap:not(.init)").each(function () {
        var $ = jQuery;
        $(this).addClass('.init');
        //Load css and js 
        var css_list = {
            css_dash: '/wp-includes/css/dashicons.min.css',
            css_editor: '/wp-includes/css/editor.min.css'
        }

        add_css_list(css_list);
        var mce_data = {
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
            tabfocus_elements: ":prev,:next", body_class: "id_wpcr3_ftext locale-en-us"}

        tinyMCEPreInit = {
            baseURL: "/wp-includes/js/tinymce",
            suffix: ".min",
            mceInit: {'id_wpcr3_ftext': mce_data, 'id_crowd_text': mce_data},
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
}