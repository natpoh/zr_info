$(document).ready(function () {
    carmaTrend();
    activityHide();
    authorAvatar();
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

    if (typeof Highcharts === 'undefined'||typeof carma_rows  === 'undefined') {
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