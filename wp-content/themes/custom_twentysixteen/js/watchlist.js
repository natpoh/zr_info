var watchlist = watchlist || {};

watchlist.mid = 0;

watchlist.show_list = function (list = {}) {
    const $row = $('<div class="row">');
    const $ul = $('<ul id="watchlist_list" class="facet-content multi_pm col_input">');

    $.each(list, function (index, item) {

        var active, checked = '';
        if (item.mid == 1) {
            active = ' active';
            checked = 'checked';
        }
        const $checkbox = `<li class="checkbox"><label class="row flex-row` + active + `">
    <span class="label check icon-check">
        <input type="checkbox" name="list[]" class="plus" value="` + item.id + `"` + checked + `>                                                      
    </span>
    <span class="t">` + watchlist.stripslashes(item.title) + ` <i class="icon-` + item.icon + `"></i></span>
    </label></li>`;

        $ul.append($checkbox);
    });
    $row.append($ul);
    return $row;
};

watchlist.stripslashes = function (str) {
    return str.replace(/\\(.)/mg, "$1");
};

watchlist.new_button = function () {
    return '<div class="row"><a class="col_input" href="#" id="new_watchlist">+ Create a new list</a></div>';
};

watchlist.click = function () {
    var data = {
        'request': 'get_lists',
        'mid': watchlist.mid
    };
    watchlist.ajax(data, function (rtn) {
        add_popup();
        var jrtn = JSON.parse(rtn);
        var wp_uid = jrtn.wp_uid;
        if (wp_uid > 0) {
            jQuery('.popup-content').html('<div id="watchlist_add" class="default_popup"></div>');
            watchlist.form(jrtn.lists);
            jQuery('input[id="action-popup"]').click();
        }
    });
};

watchlist.select_list = function ($this) {

    var activate = 1;
    if ($this.hasClass('active')) {
        activate = 0;
    }

    var data = {
        'request': 'select_list',
        'mid': watchlist.mid,
        'activate': activate,
        'type': $this.data('type')
    };

    watchlist.ajax(data, function (rtn) {
        var jrtn = JSON.parse(rtn);
        var wp_uid = jrtn.wp_uid;
        if (wp_uid > 0) {
            if (activate === 0) {
                $this.removeClass('active');
            } else {
                $this.addClass('active');
            }
        }
        if (jrtn.msg) {
            jGrowl(jrtn.msg, jrtn.theme);
        }
    });
};


watchlist.edit_watchlist_click = function (list = null) {
    add_popup();
    jQuery('.popup-content').html('<div id="watchlist_edit" class="default_popup"></div>');
    $('#watchlist_edit').html(watchlist.add_new_content(true));

    // Init form
    if (list === null) {
        list = $('#user_edit_watchlist');
    }
    var data_json = list.data('json');

    $('#wl_title').val(data_json.title);
    $('#wl_content').val(data_json.content);

    if (data_json.publish === '1') {
        $('#wl_publish').prop('checked', 'checked');
    }

    $('#submit_watchlist').click(function () {
        var publish = 0;
        if ($('#wl_publish').prop('checked')) {
            publish = 1;
        }
        var data = {
            'request': 'update',
            'title': $('#wl_title').val(),
            'content': $('#wl_content').val(),
            'publish': publish,
            'id': data_json.id
        };
        watchlist.ajax(data, function (rtn) {
            var jrtn = JSON.parse(rtn);
            var wp_uid = jrtn.wp_uid;
            if (wp_uid > 0) {
                var url = window.location.href;
                $("#page-lists article.main").load(url + " #page-lists article.main>*", function () {
                    init_nte();
                });
                $('#watchlist_edit button.close').click();
            }
        });
        return event.preventDefault();
    });
    jQuery('input[id="action-popup"]').click();
};


watchlist.list_menu_click = function ($this) {
    var action = $this.data('act');

    if (action == 'show') {
        window.location.href = $this.data('href');
        return event.preventDefault();
    }

    if (action == 'editwl') {
        watchlist.edit_watchlist_click($this);
        return event.preventDefault();
    }

    var data = {
        'request': 'list_menu',
        'id': $this.closest('.item').data('id'),
        'parent': $this.closest('.items').data('id'),
        'act': action
    };
    watchlist.ajax(data, function (rtn) {
        var jrtn = JSON.parse(rtn);
        var wp_uid = jrtn.wp_uid;
        if (wp_uid > 0) {
            var url = window.location.href;
            $("#cnt-lists").load(url + " #cnt-lists>*", function () {
                init_nte();
            });
        }
    });
    return event.preventDefault();
};

watchlist.form = function (lists) {
    let text = 'Save to';
    if (watchlist.movietype)
    {
        text = 'Save '+watchlist.movietype+' to';
    }

    $('#watchlist_add').html('<h2>'+text+':</h2><form class="row-form" id="new_watchlist_form"></form>');
    $('#new_watchlist_form').html(watchlist.show_list(lists));
    $('#new_watchlist_form').append(watchlist.new_button());
    watchlist.select();
    watchlist.add_new();
};

watchlist.select = function () {
    $('#watchlist_list input[type=checkbox]').click(function () {
        var $this = $(this);
        var act = 'add';
        if ($this.prop('checked')) {
            var label = $this.closest('label');
            label.addClass('active');
        } else {
            $this.closest('label').removeClass('active');
            act = 'remove';
        }
        var data = {
            'request': 'select',
            'id': $this.val(),
            'mid': watchlist.mid,
            'act': act
        };
        watchlist.ajax(data, function (rtn) {
            var jrtn = JSON.parse(rtn);
            var wp_uid = jrtn.wp_uid;
            if (wp_uid > 0) {
                if (jrtn.msg) {
                    jGrowl(jrtn.msg, jrtn.theme);
                }
                // Change flag
                if (jrtn.result.ret === 1 && jrtn.result.type != '0') {
                    $("#watch_block_" + watchlist.mid);
                    const listelement = $('#watch_block_' + watchlist.mid + ' [data-type="' + jrtn.result.type + '"]').first();
                    if (act === 'add') {
                        listelement.addClass('active');
                    } else {
                        listelement.removeClass('active');
                    }
                }

            }
        });
    });
};

watchlist.new_watchlist_click = function () {
    // Click in admin menu
    add_popup();
    jQuery('.popup-content').html('<div id="watchlist_add" class="default_popup"></div>');

    $('#watchlist_add').html(watchlist.add_new_content());
    $('#submit_watchlist').click(function () {
        var publish = 0;
        if ($('#wl_publish').prop('checked')) {
            publish = 1;
        }
        var data = {
            'request': 'add_new',
            'title': $('#wl_title').val(),
            'content': $('#wl_content').val(),
            'publish': publish,
            'mid': watchlist.mid
        };
        watchlist.ajax(data, function (rtn) {
            var jrtn = JSON.parse(rtn);
            var wp_uid = jrtn.wp_uid;
            if (wp_uid > 0) {

                var url = window.location.href;
                $("#cnt-lists").load(url + " #cnt-lists>*");
                init_nte();
                $('#watchlist_add button.close').click();

            }
        });
        return event.preventDefault();
    });
    jQuery('input[id="action-popup"]').click();
};

watchlist.add_new = function () {
    // Click in movie page
    $('#new_watchlist').click(function () {

        $('#watchlist_add').html(watchlist.add_new_content());
        $('#submit_watchlist').click(function () {

            var publish = 0;
            if ($('#wl_publish').prop('checked')) {
                publish = 1;
            }
            var data = {
                'request': 'add_new',
                'title': $('#wl_title').val(),
                'content': $('#wl_content').val(),
                'publish': publish,
                'mid': watchlist.mid
            };
            watchlist.ajax(data, function (rtn) {

                var jrtn = JSON.parse(rtn);
                var wp_uid = jrtn.wp_uid;
                if (wp_uid > 0) {
                    watchlist.form(jrtn.lists);
                }
                if (jrtn.msg) {
                    jGrowl(jrtn.msg, jrtn.theme);
                }

            });
            return event.preventDefault();

        });
        return event.preventDefault();
    });
};

watchlist.add_new_content = function ($edit = false) {
    var submit = 'Create';
    var title = 'Add List';
    var remove = '';
    if ($edit) {
        submit = 'Update';
        title = 'Edit List';
    }
    var form = `<h2>` + title + `</h2>                
                <div class="row">
                    <label class="col_title" for="wl_title">Title</label>
                    <div class="col_input"><input id="wl_title" type="text"></div>
                </div>
                <div class="row">                    
                    <label class="col_title">Description:</label>
                        <div class="col_input">
                            <textarea name="content" id="wl_content" class="content"></textarea>                            
                        </div>
                </div>                
                <div class="row">                
                    <div class="col_title">                     
                        <input type="checkbox" name="wl_publish" value="1" id="wl_publish">
                        <label for="wl_publish">
                            Public (Anyone can search for and view).
                        </label>                    
                    </div>                          
                </div>
                  <div class="submit_data">
                    <button id="submit_watchlist" class="button">` + submit + `</button>
                    <button class="button btn-second close">Close</button>
                  </div>`;
    return form;
};

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