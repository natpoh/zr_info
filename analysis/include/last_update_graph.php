<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class Last_update{


public function show_graph($table = 'data_movie_imdb',$update_row='add_time')
{




    ?>
<style type="text/css">
    .highcharts-root {
        font-size: 18px!important;
    }
    #container {
        height: 600px;
        min-width: 1200px;

    }
</style>
<div id="container"></div>
<button id="btn1day">1 day</button>
<button id="btn7days">7 days</button>
<button id="btn30days">30 days</button>
<button id="btn1year">1 year</button>
<button id="btnAll">All</button>



<script type="text/javascript">

    function loadScript(url, callback) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }


    if (typeof Highcharts === 'undefined' ) {
        loadScript('https://code.highcharts.com/stock/highstock.js', function() {

       //     console.log('Highstock script loaded.');
        });
    } else {

      //  console.log('Highstock is already loaded.');
    }

    document.addEventListener("DOMContentLoaded", function () {

        var chart = Highcharts.stockChart('container', {

            title: {
                text: 'Updated Records'
            },
            xAxis: {
                type: 'datetime'

            },
            yAxis: {
                title: {
                    text: 'Number of Records'
                }
            },
            series: [{
                name: 'Updated Records',
                data: []
            }],
            rangeSelector: {
                floating: true,
                y: -150,
                verticalAlign: 'bottom',


                buttons: [
                    {
                        type: 'day',
                        count: 1,
                        text: '1d'
                    },
                    {
                        type: 'day',
                        count: 7,
                        text: '7d'
                    },
                    {
                        type: 'day',
                        count: 30,
                        text: '30d'
                    },
                    {
                        type: 'year',
                        count: 1,
                        text: '1y'
                    },
                    {
                        type: 'all',
                        text: 'All',
                        max: 365 * 24 * 60 * 60 * 1000
                    }
                ]
            },
            plotOptions: {
                series: {
                    pointStart: Date.now() - 7 * 24 * 60 * 60 * 365,
                    pointInterval: 24 * 3600 * 1000,
                    marker: {
                        enabled: true
                    }
                }
            },
        });

        // Обработчики кнопок
        document.getElementById('btn1day').addEventListener('click', function () {
            chart.rangeSelector.clickButton(0);
        });

        document.getElementById('btn7days').addEventListener('click', function () {
            chart.rangeSelector.clickButton(1);
        });

        document.getElementById('btn30days').addEventListener('click', function () {
            chart.rangeSelector.clickButton(2);
        });

        document.getElementById('btn1year').addEventListener('click', function () {
            chart.rangeSelector.clickButton(3);
        });

        document.getElementById('btnAll').addEventListener('click', function () {
            chart.rangeSelector.clickButton(4);
        });


        function updateChart(startDate, endDate) {


            var params = new URLSearchParams();
            params.append('startDate', startDate);
            params.append('endDate', endDate);
            params.append('db', '<?php echo $table;?>');
            params.append('row', '<?php echo $update_row;?>');
            params.append('oper', 'get_graph');


            fetch('<?php echo WP_SITEURL;?>/analysis/jqgrid/get.php', {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                chart.series[0].setData(data);
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });

        }

        updateChart(Date.now() - 365 * 24 * 60 * 60 * 1000, Date.now());


        chart.rangeSelector.buttons.forEach(function (button, index) {
            button.events.click = function () {
                var extremes = chart.xAxis[0].getExtremes();
                var startDate = extremes.min;
                var endDate = extremes.max;

                if (index === 0) {
                    startDate = endDate - 24 * 60 * 60 * 1000; // 1 день назад
                } else if (index === 1) {
                    startDate = endDate - 7 * 24 * 60 * 60 * 1000; // 7 дней назад
                } else if (index === 2) {
                    startDate = endDate - 30 * 24 * 60 * 60 * 1000; // 30 дней назад
                } else if (index === 3) {
                    startDate = endDate - 365 * 24 * 60 * 60 * 1000; // 1 год назад
                }

                updateChart(startDate, endDate);
            };
        });
    });
</script>

<?php
}


public static function show_data()
{
    $data = $_POST;


    $table = preg_replace("/[^a-zA-Z0-9_]/", "",$data["db"]);
    $startDate = intval($data["startDate"]/1000);
    $endDate =  intval($data["endDate"]/1000);
    $update_row = preg_replace("/[^a-zA-Z0-9_]/", "",$data["row"]);


    $query = "SELECT COUNT(*) AS record_count, FLOOR(".$update_row." / 86400) * 86400 AS update_date
              FROM ".$table."
          WHERE ".$update_row."  BETWEEN $startDate AND $endDate
          GROUP BY update_date
          ORDER BY update_date";


    $result = Pdo_an::db_results_array($query);


    $dataPoints = array();
    foreach ($result as $row ) {
        $dataPoints[] = array(
            "x" => intval($row["update_date"]) * 1000,
            "y" => intval($row["record_count"])
        );
    }

    header('Content-Type: application/json');
    echo json_encode($dataPoints);


}



}