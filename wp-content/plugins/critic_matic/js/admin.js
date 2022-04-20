var wp_ajax = '/wp-admin/admin-ajax.php';
jQuery(function ($) {
    $(document).ready(function () {
        init_author_autocomplite();
        // Ajax search movie meta
        $("body").on("keyup", ".search_text", function (e) {
            var $this = $(this);
            var $holder = $this.closest('.autocomplite');
            var $results = $holder.find('.search_results').first();

            $holder.find('button.clear').click(function () {
                $this.val('');
                $holder.find('.search_id').val('');
                $results.removeClass('show');
                return false;
            });

            $holder.find('button.clear').prop('disabled', false);

            var keyword = $this.val();
            if (keyword.length > 0) {
                $.ajax({
                    type: 'GET',
                    dataType: "json",
                    url: wp_ajax,
                    data: {"action": "cm_autocomplite", "keyword": keyword},
                    success: function (response) {
                        if (response.type == "ok") {
                            $results.html('');
                            for (var i = 0; i < response.data.length; i++) {
                                var id = response.data[i]['id'];
                                var title = response.data[i]['title'];
                                $results.append('<div class="result" data-id="' + id + '" data-title="' + title + '">' + title + ' [' + id + ']</div>');
                            }
                        }
                        if (!$results.hasClass('show')) {
                            $results.addClass('show');
                        }

                        $holder.find('.result').click(function () {
                            var $this_res = $(this);
                            $this.val($this_res.attr('data-title'));
                            $holder.find('.search_id').val($this_res.attr('data-id'));
                            $results.removeClass('show');
                            return false;
                        });
                    }
                });
            }
        });

        $('select.bulk-actions').on('change', function (e) {
            var optionSelected = $("option:selected", this);
            var valueSelected = this.value;
            if (valueSelected == 'changeauthor') {
                $('select.bulk-actions').after('<div class="author-autocomplite autocomplite">\n\
<input type="text" placeholder="New author Name or Id" class="change_author autocomplite">\n\
<button class="clear button" disabled>Clear</button>\n\
<input type="hidden" name="author_id" class="author_id" value="">\n\
<div class="search_results"></div></div>')
                init_author_autocomplite();
            } else {
                $('.change_author').remove();
            }
        });
        $('#add-campaing-type').on('change', function (e) {
            var optionSelected = $("option:selected", this);
            var valueSelected = this.value;
            console.log(valueSelected);
            $('#campaign').attr('class', 'cm-type-' + valueSelected);
        });

        $('#find-channel').click(function () {
            var yt_query = $('#yt_find').val();
            var button = $(this);
            if (!yt_query) {
                return false;
            }

            if (button.hasClass('disabled')) {
                return false;
            }
            
            $('#campaign').removeClass('yt_channel_valid');
            
            button.addClass('disabled');

            $.ajax({
                type: 'GET',
                dataType: "json",
                url: wp_ajax,
                data: {"action": "cm_find_yt_channel", "yt_query": yt_query},
                success: function (response) {
                    button.removeClass('disabled');
                    if (response.valid == 1) {
                        $('#campaign').addClass('yt_channel_valid');
                        $('#yt_page').val(response.channel);
                        $('#title').val(response.title);
                        $('#site').val('https://www.youtube.com/channel/'+response.channel);
                        $('#total_found').text(response.total);                        
                        $('#error').text('');

                    } else {
                        $('#error').text(response.err);
                    }
                    console.log(response);
                    //{"err":"","total":489,"channel":"UC337i8LcUSM4UMbLf820I8Q","valid":1}
                    return false;
                }
            });

            return false;
        });

    });

    function init_author_autocomplite() {
        $('.change_author:not(.init)').each(function () {
            var $this = $(this);
            $this.addClass('init');
            $this.keyup(function (e) {
                var $holder = $this.closest('.author-autocomplite');
                var $results = $holder.find('.search_results').first();

                $holder.find('button.clear').click(function () {
                    $this.val('');
                    $holder.find('.author_id').val('');
                    $results.removeClass('show');
                    return false;
                });

                $holder.find('button.clear').prop('disabled', false);

                var keyword = $this.val();
                if (keyword.length >= 2) {
                    $.ajax({
                        type: 'GET',
                        dataType: "json",
                        url: wp_ajax,
                        data: {"action": "cm_author_autocomplite", "keyword": keyword},
                        success: function (response) {
                            if (response.type == "ok") {
                                $results.html('');
                                for (var i = 0; i < response.data.length; i++) {
                                    var id = response.data[i]['id'];
                                    var title = response.data[i]['title'];
                                    $results.append('<div class="result" data-id="' + id + '" data-title="' + title + '">' + title + ' [' + id + ']</div>');
                                }
                            }
                            if (!$results.hasClass('show')) {
                                $results.addClass('show');
                            }

                            $holder.find('.result').click(function () {
                                var $this_res = $(this);
                                $this.val($this_res.attr('data-title'));
                                $holder.find('.author_id').val($this_res.attr('data-id'));
                                $results.removeClass('show');
                                return false;
                            });
                        }
                    });
                }
            });
        });
    }
});
