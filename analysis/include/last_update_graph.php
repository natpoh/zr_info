<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class Last_update{


public function show_graph($table = 'data_movie_imdb',$update_row='add_time',$request=[])
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
            reload_data(0);
        });

        document.getElementById('btn7days').addEventListener('click', function () {
            chart.rangeSelector.clickButton(1);
            reload_data(1);
        });

        document.getElementById('btn30days').addEventListener('click', function () {
            chart.rangeSelector.clickButton(2);
            reload_data(2);
        });

        document.getElementById('btn1year').addEventListener('click', function () {
            chart.rangeSelector.clickButton(3);
            reload_data(3);
        });

        document.getElementById('btnAll').addEventListener('click', function () {
            chart.rangeSelector.clickButton(4);
            reload_data(4);
        });



        function reload_data(index) {

                    var startDate = 0;
                    var endDate = Date.now();


            if (index == 0) {
                        startDate = Number(endDate) - 24 * 60 * 60 * 1000; // 1 день назад
                    } else if (index == 1) {
                        startDate =  Number(endDate) - 7 * 24 * 60 * 60 * 1000; // 7 дней назад
                    } else if (index == 2) {
                        startDate =  Number(endDate) - 30 * 24 * 60 * 60 * 1000; // 30 дней назад
                    } else if (index == 3) {
                        startDate =  Number(endDate) - 365 * 24 * 60 * 60 * 1000; // 1 год назад
                    }
            else if (index == 4) {
                startDate =  Number(endDate) - 365 *5 * 24 * 60 * 60 * 1000; // 5 лет
            }

            var groupType =  'daily';

            if (index < 2) {
                groupType =  'hourly';
                chart.update({
                    plotOptions: {
                        series: {
                            pointInterval: 3600 * 1000
                        }
                    }
                });
            } else {
                chart.update({
                    plotOptions: {
                        series: {
                            pointInterval: 24 * 3600 * 1000
                        }
                    }
                });
            }

                    updateChart(startDate, endDate,groupType);

        }

        function updateChart(startDate, endDate, groupType) {
            var params = new URLSearchParams();
            params.append('startDate', startDate);
            params.append('endDate', endDate);
            params.append('db', '<?php echo $table;?>');
            params.append('row', '<?php echo $update_row;?>');
            params.append('request', '<?php echo json_encode($request);?>');
            params.append('oper', 'get_graph');
            params.append('groupType', groupType); // Передача типа группировки

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



    });
</script>

<?php
}


public static function prepare_request($qr)
{
    $where1='';
    if ($qr) {
        foreach ($qr as $i => $v) {
            if (strstr($v, 'like=') && strpos($v, 'like') === 0) {

                $v = substr($v, 5);
                $where1 .= " AND `" . $i . "` LIKE '%" . $v . "%' ";

            } else if (strstr($v, 'lower=') && strpos($v, 'lower') === 0) {

                $v = substr($v, 6);
                $where1 .= " AND `" . $i . "` < '" . $v . "' ";

            } else if (strstr($v, 'larger=') && strpos($v, 'larger') === 0) {

                $v = substr($v, 7);
                $where1 .= " AND `" . $i . "` > '" . $v . "' ";

            } else if (strstr($v, 'not_equal') && strpos($v, 'not_equal') === 0) {

                $v = substr($v, 9);
                $where1 .= " AND `" . $i . "` != '" . $v . "' ";

            }


        }

    }

return $where1;
}
public static function show_data()
{
    $data = $_POST;

    $table = preg_replace("/[^a-zA-Z0-9_]/", "",$data["db"]);
    $startDate = intval($data["startDate"]/1000);
    $endDate =  intval($data["endDate"]/1000);
    $update_row = preg_replace("/[^a-zA-Z0-9_]/", "",$data["row"]);
    $groupType = isset($data["groupType"]) ? $data["groupType"] : 'daily'; // Получение типа группировки
    $where1 ='';
    if ($data['request']) {
        $qr = json_decode(stripslashes($data['request']));
    }
    if ($data['request_string']) {
        parse_str( $data['request_string'],$qr);
    }


            if ($qr)
            {
                $where1 =  self::prepare_request($qr);

            }

    if ($groupType === 'hourly') {
        $query = "SELECT COUNT(*) AS record_count, FLOOR(".$update_row." / 3600) * 3600 AS update_date
              FROM ".$table."
              WHERE ".$update_row."  BETWEEN $startDate AND $endDate ".$where1."
              GROUP BY update_date
              ORDER BY update_date";
    } else {
        $query = "SELECT COUNT(*) AS record_count, FLOOR(".$update_row." / 86400) * 86400 AS update_date
              FROM ".$table."
              WHERE ".$update_row."  BETWEEN $startDate AND $endDate ".$where1."
              GROUP BY update_date
              ORDER BY update_date";
    }

//echo $query;
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