$(document).ready(function () {
    carmaTrend();
    activityHide();
    authorAvatar();
    uploadAvatar();
});

function authorAvatar() {
    $('#change_avatar').click(function () {
        var $this = $(this);
        if ($this.hasClass('active')) {
            return false;
        }
        $this.addClass('active');

        var avfile = 'user_avatar.php';
        var url = '/service/' + avfile;

        var ajaxData = {ajaxAct: 'random_avatar'};
        $.ajax({
            type: "POST",
            url: url,
            data: ajaxData,
            dataType: "json",
            success: function (rtn) {
                if (rtn.success) {
                    $('.uavh .uavatar img').attr('srcset', rtn.avatar);
                }
                $this.removeClass('active');
            },
            error: function (rtn) {
                alert('An unknown error has occurred.');
                $this.removeClass('active');
            }
        });

        return false;
    });
}

function carmaTrend() {

    if (typeof Highcharts === 'undefined' || typeof carma_rows === 'undefined') {
        return false;
    }

    Highcharts.chart('carma-trend', {
        chart: {
            zoomType: 'xy',
            height: 500,
        },
        title: {
            text: 'Interaction score trend'
        },

        xAxis: {
            type: 'category',
            title: {
                text: 'Date'
            },
        },
        yAxis: {
            title: {
                text: 'Score',
            },
        },
        legend: {
            maxHeight: 70,
        },
        tooltip: {
            pointFormat: 'Score: <b>{point.y}</b>',
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
            }
        },
        series: [{
                name: 'Score',
                data: carma_rows
            }]
    });
}
function activityHide() {
    //ShowOrHide
    jQuery('.activity-day div.title').each(function () {
        var $this = jQuery(this);

        $this.click(function () {
            var tid = jQuery(this).attr('tid');
            showOrHide(tid);
            if ($this.hasClass('inactive')) {
                $this.removeClass('inactive');
            } else {
                $this.addClass('inactive');
            }
        });
    });
}

function showOrHide(id) {
    var block = document.getElementById(id).style;
    if (block.display == 'none') {
        block.display = 'block';
    } else {
        block.display = 'none';
    }
}


function uploadAvatar() {
    $('#select_av_type:not(.init)').each(function () {
        var $this = $(this);
        $this.addClass('init');
        var cm_url = '/wp-content/plugins/critic_matic/';

        $("#select_av_type input").change(function () {
            if ($(this).is(":checked")) {
                var select = $(this).val();
                $('.av_action.active').removeClass('active');
                $('#av_action_' + select).addClass('active');
                // TODO ajax change default avatar, reload user avatar
                //console.log(select);
                var author = $('#author_id');
                var av_size = $('#author_image').attr('data-size');
                $.ajax({
                    url: cm_url + "ajax/ajax_pro_img.php",
                    type: "POST",
                    data: {"author_id": author.attr('data-id'), "change_type": 1, "av_type": select, "av_size": av_size},
                    success: function (data) {
                        $('#author_image').html(data);
                    }
                });
            }
        });
    });


    $('#upl_avatar:not(.init)').each(function () {

        var $this = $(this);
        $this.addClass('init');

        var cm_url = '/wp-content/plugins/critic_matic/';
        var server_url = 'https://info.antiwoketomatoes.com';
        //var server_url = '';

        var css_list = {
            css_croppie: cm_url + 'css/croppie.css',
        }

        add_css_list(css_list);

        var success = function () {


            $image_crop = null;

            $('#upl_avatar').on('click', function () {
                $('#avatar_file').click();
                return false;
            });

            $('#avatar_file').on('change', function () {

                $('#msgh').html('');
                let allowedExtension = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                let type = this.files[0].type;
                if (allowedExtension.indexOf(type) == -1) {
                    $('#msgh').html('<div class="msg-content msg error">This image is invalid. Supported only: .jpg, .png, .gif</div>');
                    return false;
                }

                $('#author_image').hide();
                $('#upl_avatar').hide();

                $('#upload-image').show();
                $('.cropped_images').show();

                $image_crop = $('#upload-image').croppie({
                    enableExif: true,
                    viewport: {
                        width: 200,
                        height: 200,
                        type: 'square'
                    },
                    boundary: {
                        width: 300,
                        height: 300
                    }
                });

                var reader = new FileReader();
                reader.onload = function (e) {
                    $image_crop.croppie('bind', {
                        url: e.target.result
                    }).then(function () {
                        // console.log('jQuery bind complete');
                    });
                }
                reader.readAsDataURL(this.files[0]);
            });

            $('#cropped_image').on('click', function (ev) {
                var author = $('#author_id');
                if (author.hasClass('proccess')) {
                    return false;
                }

                if ($image_crop === null) {
                    return false;
                }

                author.addClass('proccess');


                $image_crop.croppie('result', {
                    type: 'canvas',
                    size: 'viewport'
                }).then(function (response) {
                    $.ajax({
                        url: server_url + cm_url + "ajax/ajax_pro_img.php",
                        type: "POST",
                        data: {"image": response, "author_id": author.attr('data-id'), "no_upd": 1},
                        success: function (data) {
                            const ret = JSON.parse(data);
                            if (ret.filename) {
                                update_author_filename(ret.filename);
                            } else {
                                var reason = 'Image loading error';
                                if (ret.error) {
                                    reason = ret.reason;
                                }
                                $('#msgh').html('<div class="msg-content msg error">' + reason + '</div>');
                            }
                            html = '<img src="' + response + '" />';
                            $("#author_image").html(html);
                        }
                    });
                });
                return false;
            });

            $('#cropped_cancel').on('click', function (ev) {
                cropped_success();
                return false;
            });

            function update_author_filename(filename = '') {
                var author = $('#author_id');
                $.ajax({
                    url: cm_url + "/ajax/ajax_pro_img.php",
                    type: "POST",
                    data: {"author_id": author.attr('data-id'), "filename": filename},
                    success: function (data) {
                        author.removeClass('proccess');
                        cropped_success();
                        $('#msgh').html('<div class="msg-content">Image uploaded successfully</div>');
                    }
                });
            }

            function cropped_success() {
                $('#upload-image').hide();
                $('.cropped_images').hide();

                $('#author_image').show();
                $('#upl_avatar').show();

                $image_crop = null;
                $('#upload-image').html('');
                $('#avatar_file').val('');
            }
        }

        var third_scripts = {
            jsutils: '/wp-content/plugins/critic_matic/js/croppie.js',
        };
        
        use_ext_js(success, third_scripts);
    });
}


