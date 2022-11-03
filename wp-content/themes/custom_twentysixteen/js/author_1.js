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
    $('#carma-trend').each(function () {
        google.load('visualization', '1', {packages: ['corechart']});
        google.setOnLoadCallback(drawChart);
        function drawChart() {

            var datacarma = new google.visualization.DataTable();
            datacarma.addColumn('string', 'Дата');
            datacarma.addColumn('number', 'Карма');
            datacarma.addRows(carma_rows);
            var width = jQuery('.chartholder').width() + 50;
            var height = width / 2;
            if (height < 300) {
                height = 300;
            }
            var options = {
                width: width,
                height: height,
                legend: {
                    position: 'in'
                },
            };
            options.colors = ['green'];
            var chartcarma = new google.visualization.LineChart(
                    document.getElementById('carma-trend'));
            chartcarma.draw(datacarma, options);
        }
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