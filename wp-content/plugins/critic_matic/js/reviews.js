/*custom select*/
// Iterate over each select element

function listen(box, e)
{
    let cw = 0;
    let rs = 0;

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

    rs = (cw / wd) * 5;

    rs = Number(rs);

    if (box.classList.contains('rating'))
    {
        if (rs < 0.2)
            rs = 0;
        if (rs > 0 && rs < 0.5)
            rs = 0.5;
        if (rs > 4.9)
            rs = 5;
        rs = Number(rs.toFixed(1));
    } else
    {
        if (rs < 0.2)
            rs = 0;
        rs = Math.ceil(Number(rs));
    }

    let prnt = box.parentElement;
    let number_block = prnt.querySelector(".rating_number_rate");

    let r_color = Math.ceil(Number(rs));
    number_block.textContent = rs;
    number_block.classList.remove("number_rate_0", "number_rate_1", "number_rate_2", "number_rate_3", "number_rate_4", "number_rate_5");
    number_block.classList.add('number_rate_' + r_color);

}
function listen_click(box, e, force = 0) {
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
    if (box.classList.contains('rating'))
    {
        if (rz > 0 && rz < 0.5)
            rz = 0.5;
        if (rz > 4.9)
            rz = 5;
        rz = Number(rz.toFixed(1));
    } else
    {
        rz = Math.ceil(Number(rz));
    }

    if (force)
    {
        rz = 5;
    }

    let r_color = Math.ceil(Number(rz));

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
    number_block.classList.add('number_rate_' + r_color);

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


    document.querySelectorAll('.rating_container .rating_number').forEach(ratingNumber => {
        ratingNumber.addEventListener('click', function (e) {


            const container = ratingNumber.closest('.rating_container');


            const ratingResult = container.querySelector('.rating_result');


            listen_click(ratingResult, e, 1);

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

            let prnt = box.parentElement;
            let number_block = prnt.querySelector(".rating_number_rate");
            number_block.textContent = 0;
            number_block.classList.remove("number_rate_0", "number_rate_1", "number_rate_2", "number_rate_3", "number_rate_4", "number_rate_5");



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
    var url = '/service/ajax/audience.php';
    //if (host == "zeitgeistreviews.com") {
    //    var url = 'https://service.zeitgeistreviews.com/ajax/audience.php';
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
                wpcr3a.msg(rtn.err);
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
            wpcr3a.msg('An unknown error has occurred.');
            wpcr3a.enableSubmit();
        }
    });
};

wpcr3a.msg = function (t, type = 'error') {
    var $ = jQuery;
    $('.msg-data').html('<div class="msg ' + type + '">' + t + '</div>');
    var msg_holder = $('.msg-holder');
    msg_holder.show();
    msg_holder.addClass('active');
    $('.popup-inner').scrollTop(0);

    $("#audience_respond .text-input").on("change keyup paste", function () {
        var msg_holder = $('.msg-holder');
        if (msg_holder.hasClass('active')) {
            msg_holder.removeClass('active');
            msg_holder.hide();
        }
    });
}

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
            var label = div2.find('label[for="' + v.attr('id') + '"] .rtitle'), err = '';
            if (label.length) {
                err = $.trim(label.text().replace(':', '')) + ' is required.<br />';
            } else {
                err = 'A required field has not been filled out.';
            }
            req.push(err);
        }
    });

    if (req.length > 0) {
        req = req.join("\n");
        wpcr3a.msg(req);
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
        jQuery('.wpcr3_respond_1').html('<div class="succes_send">Thank you! Your review has been received and will be posted immediately after it passes anti-troll inspection.</div>');
        // user menu hook
        if (typeof author_review_upd !== "undefined") {
            author_review_upd();
        }
        if ($('#bs_modal .modal-footer .btn-primary').length) {
            bs_modal.footer('','Close');            
        }
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

    $("body").on("input", "#wpcr3_fname", function () {
        var v = $(this);
        var keyword = v.val();
        if (keyword.length > 0) {
            $('#audience_respond').removeClass('anon');
            $('#wpcr3_femail').addClass('wpcr3_required');
        } else {
            $('#audience_respond').addClass('anon');
            $('#wpcr3_femail').removeClass('wpcr3_required');
        }
        return false;
    });


    $("#wp-id_wpcr3_ftext-wrap:not(.init)").each(function () {
        $(this).addClass('.init');
        if (typeof wp_custom_editor == 'object') {
            wp_custom_editor.load('id_wpcr3_ftext');
        } else {
            success = function () {
                wp_custom_editor.load('id_wpcr3_ftext');
            };
            var third_scripts = {
                wpeditorjs: '/wp-content/themes/custom_twentysixteen/js/wp-editor.js?v=1.16'
            };
            use_ext_js(success, third_scripts);
        }

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

