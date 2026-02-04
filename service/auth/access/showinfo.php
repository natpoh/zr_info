<?php
include 'mode.php';

$period = getPeriod();
$count = $period * 60;

//Получаем дату
$data = getLogDataFromDB($count, $pdo_connect_data);

//Компануем дату
$periods = getPeriods();
$mod = $periods[$period]['mod'];

$data = groupData($data, $mod);

//Отображаем дату
renderData($data);

function getLogDataFromDB($count, $pdo_connect_data) {
    $data = array();
    if ($pdo_connect_data) {

        // Сохраняем данные в базу
        $dbhost = 'mysql:host=' . $pdo_connect_data['host'] . ';dbname=' . $pdo_connect_data['db'];
        $dbh = new SafePDO($dbhost, $pdo_connect_data['user'], $pdo_connect_data['pass']);


        // Добавляем значения в таблицу
        $query = sprintf("SELECT cpu, date, info, memtotal,memfree,buffers,cached,dirty,slab,swaptotal,swapfree,memavailable FROM info ORDER BY id DESC limit %d", $count);

        $sth = $dbh->prepare($query);

        $sth->execute();

        $data = $sth->fetchAll();




        // соединение больше не нужно, закрываем
        $sth = null;
        $dbh = null;
    }
    return $data;
}

//Компануем данные в соответствии с модифиактором
/*
  [0] => Array
  (
  [cpu] => 32
  [0] => 32
  [date] => 1500224531
  [1] => 1500224531
  [info] => a:3:{s:6:"SERVER";a:8:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.068";s:6:"b_sent";i:395234;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:4:"host";a:8:{s:3:"req";i:0;s:2:"ip";i:1;s:6:"status";s:0:"";s:8:"req_time";s:1:"0";s:6:"b_sent";i:0;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:6:"unknow";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.068";s:6:"b_sent";i:395234;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}}
  [2] => a:3:{s:6:"SERVER";a:8:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.068";s:6:"b_sent";i:395234;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:4:"host";a:8:{s:3:"req";i:0;s:2:"ip";i:1;s:6:"status";s:0:"";s:8:"req_time";s:1:"0";s:6:"b_sent";i:0;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:6:"unknow";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.068";s:6:"b_sent";i:395234;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}}
  )
 */
function groupData($data, $mod) {
    if ($mod == 1) {
        return $data;
    }

    $ret = array();
    $i = 1;
    if (sizeof($data) > 0) {
        $group = array();
        foreach ($data as $item) {
            //cpu
            $group['cpu'] += $item['cpu'] / $mod;
            // memfree
            $group['memfree'] += $item['memfree'] / $mod;
            $group['swapfree'] += $item['swapfree'] / $mod;
            $group['memavailable'] += $item['memavailable'] / $mod;
            
            //date
            if (!isset($group['date'])) {
                $group['date'] = $item['date'];
            }
            //info
            $info = unserialize($item['info']);

            //hosts
            foreach ($info as $host => $hostinfo) {

                //hostinfo
                foreach ($hostinfo as $key => $value) {
                    if ($key == 'status') {
                        foreach ($value as $skey => $sval) {
                            $group['info'][$host]['status'][$skey] += $sval;
                        }
                        continue;
                    }

                    $group['info'][$host][$key] += $value;
                }
            }
            $i++;
            if ($i >= $mod) {
                $group['cpu'] = round($group['cpu'], 0);
                $group['memfree'] = round($group['memfree'], 0);
                $group['swapfree'] = round($group['swapfree'], 0);
                $group['memavailable'] = round($group['memavailable'], 0);
                $ret[] = $group;
                $i = 0;
                $group = array();
            }
        }
    }
    return $ret;
}

/*
  Array
  (
  [cpu] => 276
  [0] => 276
  [date] => 1498999906
  [1] => 1498999906
  [info] => a:9:{s:6:"SERVER";a:8:{s:3:"req";i:324;s:2:"ip";i:122;s:6:"status";a:5:{i:200;i:293;i:301;i:12;i:302;i:16;i:500;i:2;i:304;i:1;}s:8:"req_time";s:5:"95.63";s:6:"b_sent";i:8337486;s:8:"bots_req";i:165;s:7:"bots_ip";i:30;s:9:"bots_time";s:6:"49.334";}s:4:"host";a:8:{s:3:"req";i:0;s:2:"ip";i:1;s:6:"status";s:0:"";s:8:"req_time";s:1:"0";s:6:"b_sent";i:0;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:14:"pandoraopen.ru";a:8:{s:3:"req";i:305;s:2:"ip";i:107;s:6:"status";a:5:{i:200;i:279;i:301;i:12;i:302;i:11;i:500;i:2;i:304;i:1;}s:8:"req_time";s:6:"87.015";s:6:"b_sent";i:8133696;s:8:"bots_req";i:155;s:7:"bots_ip";i:21;s:9:"bots_time";s:6:"44.519";}s:13:"motivatory.ru";a:8:{s:3:"req";i:6;s:2:"ip";i:6;s:6:"status";a:2:{i:302;i:5;i:200;i:1;}s:8:"req_time";s:5:"0.271";s:6:"b_sent";i:13817;s:8:"bots_req";i:6;s:7:"bots_ip";i:6;s:9:"bots_time";s:5:"0.271";}s:7:"zema.su";a:8:{s:3:"req";i:7;s:2:"ip";i:7;s:6:"status";a:1:{i:200;i:7;}s:8:"req_time";s:4:"7.89";s:6:"b_sent";i:113130;s:8:"bots_req";i:3;s:7:"bots_ip";i:3;s:9:"bots_time";s:5:"4.544";}s:14:"emelianovip.ru";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.176";s:6:"b_sent";i:23670;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:17:"benihis-tyumen.ru";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.104";s:6:"b_sent";i:31477;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:29:"xn----8sbef3a2ac1a3j.xn--p1ai";a:7:{s:3:"req";i:1;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:1;}s:8:"req_time";s:5:"0.174";s:6:"b_sent";i:7926;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:14:"bzsk-invest.ru";a:8:{s:3:"req";i:1;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:1;}s:8:"req_time";s:1:"0";s:6:"b_sent";i:13770;s:8:"bots_req";i:1;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}}
  [2] => a:9:{s:6:"SERVER";a:8:{s:3:"req";i:324;s:2:"ip";i:122;s:6:"status";a:5:{i:200;i:293;i:301;i:12;i:302;i:16;i:500;i:2;i:304;i:1;}s:8:"req_time";s:5:"95.63";s:6:"b_sent";i:8337486;s:8:"bots_req";i:165;s:7:"bots_ip";i:30;s:9:"bots_time";s:6:"49.334";}s:4:"host";a:8:{s:3:"req";i:0;s:2:"ip";i:1;s:6:"status";s:0:"";s:8:"req_time";s:1:"0";s:6:"b_sent";i:0;s:8:"bots_req";i:0;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}s:14:"pandoraopen.ru";a:8:{s:3:"req";i:305;s:2:"ip";i:107;s:6:"status";a:5:{i:200;i:279;i:301;i:12;i:302;i:11;i:500;i:2;i:304;i:1;}s:8:"req_time";s:6:"87.015";s:6:"b_sent";i:8133696;s:8:"bots_req";i:155;s:7:"bots_ip";i:21;s:9:"bots_time";s:6:"44.519";}s:13:"motivatory.ru";a:8:{s:3:"req";i:6;s:2:"ip";i:6;s:6:"status";a:2:{i:302;i:5;i:200;i:1;}s:8:"req_time";s:5:"0.271";s:6:"b_sent";i:13817;s:8:"bots_req";i:6;s:7:"bots_ip";i:6;s:9:"bots_time";s:5:"0.271";}s:7:"zema.su";a:8:{s:3:"req";i:7;s:2:"ip";i:7;s:6:"status";a:1:{i:200;i:7;}s:8:"req_time";s:4:"7.89";s:6:"b_sent";i:113130;s:8:"bots_req";i:3;s:7:"bots_ip";i:3;s:9:"bots_time";s:5:"4.544";}s:14:"emelianovip.ru";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.176";s:6:"b_sent";i:23670;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:17:"benihis-tyumen.ru";a:7:{s:3:"req";i:2;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:2;}s:8:"req_time";s:5:"0.104";s:6:"b_sent";i:31477;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:29:"xn----8sbef3a2ac1a3j.xn--p1ai";a:7:{s:3:"req";i:1;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:1;}s:8:"req_time";s:5:"0.174";s:6:"b_sent";i:7926;s:7:"bots_ip";i:0;s:9:"bots_time";s:0:"";}s:14:"bzsk-invest.ru";a:8:{s:3:"req";i:1;s:2:"ip";i:1;s:6:"status";a:1:{i:200;i:1;}s:8:"req_time";s:1:"0";s:6:"b_sent";i:13770;s:8:"bots_req";i:1;s:7:"bots_ip";i:1;s:9:"bots_time";s:1:"0";}}
  )

 */

function renderData($data) {
    if (sizeof($data)) {
        $i_len = sizeof($data) - 1;
        $time_end = $data[0]['date'];
        $time_first = $data[$i_len]['date'];
        $host_name = getHostNameByReq();
        ?>
        <!DOCTYPE HTML>
        <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">

                <link rel="stylesheet" href="tablesorter/jq.css" type="text/css" media="print, projection, screen" />
                <link rel="stylesheet" href="tablesorter/blue/style.css" type="text/css" id="" media="print, projection, screen" />

                <script type="text/javascript" src="tablesorter/jquery-latest.js"></script> 
                <script type="text/javascript" src="tablesorter/jquery.tablesorter.min.js"></script>
                <script type="text/javascript" id="js">$(document).ready(function () {
                        // call the tablesorter plugin
                        $("table").tablesorter();
                    });</script>
                <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

                <style>
                    body {
                        display: block;
                        margin: 8px;
                    }
                    table {
                        border: 1px solid gray;
                        border-spacing: 0px;
                        border-collapse: separate;
                    }
                    td, th {    
                        border-spacing: 0px;
                        border: 1px solid gray;
                        padding: 5px;

                    }
                    a.active {
                        font-weight: bold;
                        color: black;
                    }

                </style>
                <title>Server info</title>
            </head>
            <body>
                <h1>Информация о системе</h1>
                <?php getPeriodMenu(); ?>
                <p>Период <?php print gmdate('d.m.y H:i:s', $time_first + 18000); ?> — <?php print gmdate('d.m.y H:i:s', $time_end + 18000); ?>. GMT +5</p>
                <?php
                //Получаем список хостов и готовим массив
                //$newdata = array();
                $hosts = array();
                $hostnames = array();
                $cpuarr = array();
                $memfree_arr = array();
                $swapfree_arr = array();
                $memavailable_arr = array();
                foreach ($data as $item) {
                    if (is_string($item['info'])) {
                        $info = unserialize($item['info']);
                    } else {
                        $info = $item['info'];
                    }
                    $date = $item['date'];

                    $cpuarr[$date] = $item['cpu'];
                    $memfree_arr[$date] = $item['memfree'];
                    $swapfree_arr[$date] = $item['swapfree'];
                    $memavailable_arr[$date] = $item['memavailable'];

                    foreach ($info as $host => $hostinfo) {
                        $hosts[$host][$date] = $hostinfo;
                        if (isset($hostnames[$host])) {
                            $hostnames[$host] += 1;
                        } else {
                            $hostnames[$host] = 1;
                        }
                    }

                    //$newdata[] = array('cpu' => $cpu, 'date' => $item['date'], 'info' => $info);
                }
                $data = null;
                arsort($hostnames);
                ksort($cpuarr);
                ksort($memfree_arr);
                ksort($swapfree_arr);
                ksort($memavailable_arr);
                //p_r($hostnames);
                //p_r($cpuarr);
                //p_r($hosts);
                //Hosts info
                $total_info = hosts_total_info($hostnames, $hosts);

                //Показываем список хостов, с процентным соотношением по времени
                $hostsPrecent = getHostsPrecent($total_info, 'SERVER', 'req_time');
                renderHostPercent($hostsPrecent, $host_name);

                /* Array
                  (
                  [SERVER] => Array
                  (
                  [req] => 17851
                  [ip] => 8352
                  [status] => Array
                  (
                  [200] => 15096
                  [499] => 65
                  [302] => 1226
                  [301] => 1024
                  [444] => 207
                  [304] => 81
                  [404] => 83
                  [500] => 29
                  [403] => 25
                  [408] => 5
                  [206] => 10
                  )

                  [req_time] => 7732.798
                  [b_sent] => 453593907
                  [bots_req] => 6513
                  [bots_ip] => 1891
                  [bots_time] => 3993.583
                  )

                 */
                //render SERVER statuses

                renderStatusHost($total_info, $host_name);

                //Server info
                print_graphics($cpuarr, $memfree_arr, $swapfree_arr, $memavailable_arr, $hosts);


                //Host table

                $hostdata = $hosts[$host_name];
                render_host_info_table($host_name, $hostdata, $cpuarr, $memfree_arr, $swapfree_arr);
                ?>


            </body>
        </html>
        <?php
    }
}

function getPeriodMenu() {
    $periods = getPeriods();
    $current = getPeriod();
    $host_name = getHostNameByReq();
    ?>
    <p>Временной интервал:
        <?php
        foreach ($periods as $key => $value) {
            $class = '';
            if ($current == $key) {
                $class = ' class="active" ';
            }
            ?>
            |  <a href="?h=<?php print $host_name ?>&per=<?php print $key ?>"<?php print $class ?>><?php print $value['title'] ?></a>
        <?php }
        ?>            
    </p>
    <p>Группировка по <?php print $periods[$current]['mod']; ?> мин.</p>
    <?php
}

function getHostNameByReq() {
    if (isset($_GET['h'])) {
        $name = $_GET['h'];
    } else {
        $name = 'SERVER';
    }
    return $name;
}

function getPeriods() {

    $periods = array(
        1 => array('title' => 'Час', 'mod' => 1),
        3 => array('title' => '3 часа', 'mod' => 2),
        6 => array('title' => '6 часов', 'mod' => 5),
        12 => array('title' => '12 часов', 'mod' => 10),
        24 => array('title' => 'Сутки', 'mod' => 15),
        168 => array('title' => 'Неделя', 'mod' => 60),
        720 => array('title' => '30 дней', 'mod' => 1440)
    );
    return $periods;
}

function getPeriod() {
    $defperiod = 1;
    $current = $defperiod;

    $periods = getPeriods();
    $period = (int) $_GET['per'];

    if ($period && isset($periods[$period])) {
        $current = $period;
    }

    return $current;
}

function renderHostPercent($hostsPrecent, $host_name) {
    if (sizeof($hostsPrecent) > 0) {
        $per = getPeriod();
        $ret = array();
        foreach ($hostsPrecent as $host => $pr) {
            if ($pr >= 0.1) {
                $active = '';
                if ($host_name == $host) {
                    $active = ' class="active" ';
                }
                $ret[] = '<a href="?h=' . $host . '&per=' . $per . '"' . $active . '>' . $host . '<a/>:' . $pr . '%';
            }
        }
        print '<p>' . implode(', ', $ret) . '</p>';
    }
}

/*
  [SERVER] => Array
  (
  [req] => 17851
  [ip] => 8352
  [req_time] => 7732.798
  [b_sent] => 453593907
  [bots_req] => 6513
  [bots_ip] => 1891
  [bots_time] => 3993.583
  )
 */

function getHostsPrecent($total_info, $from, $key) {
    if (!isset($total_info[$from][$key])) {
        return;
    }
    $from_data = $total_info[$from][$key];
    $ret = array();
    $ret[$from] = 100;
    if (sizeof($total_info) > 0) {
        foreach ($total_info as $server => $info) {
            if (isset($info[$key])) {
                $ret[$server] = round($info[$key] / $from_data * 100, 2);
            }
        }
    }
    arsort($ret);
    return $ret;
}

function hosts_total_info($hostnames, $hosts) {
    $total = array();
    foreach ($hostnames as $name => $count) {
        $hostdata = isset($hosts[$name]) ? $hosts[$name] : array();

        if ($hostdata) {
            /*    [1500224531] => Array
              (
              [req] => 2
              [ip] => 1
              [status] => Array
              (
              [200] => 2
              )

              [req_time] => 0.068
              [b_sent] => 395234
              [bots_req] => 0
              [bots_ip] => 1
              [bots_time] => 0
              ) */
            foreach ($hostdata as $data) {
                foreach ($data as $key => $value) {
                    if ($key == 'status') {
                        foreach ($value as $st => $n) {
                            $total[$name][$key][$st] += $n;
                        }
                        continue;
                    }

                    if (!$value) {
                        $value = 0;
                    }
                    $total[$name][$key] += $value;
                }
            }
        }
    }
    return $total;
}

function renderStatusHost($total_info, $host_name) {
    if (isset($total_info[$host_name])) {
        if (isset($total_info[$host_name]['status'])) {
            global $statuses;
            $statuses = $total_info[$host_name]['status'];
            if (sizeof($statuses) > 0) {
                ksort($statuses);
                $ret = array();
                $stlocal = $statuses;
                arsort($stlocal);

                $all = 0;
                foreach ($stlocal as $st => $v) {
                    $all += $v;
                }

                foreach ($stlocal as $st => $v) {

                    $persent = round($v * 100 / $all, 2);
                    if ($persent > 10) {
                        $persent = '<b>' . $persent . '</b>';
                    }
                    $ret[] = "<b>$st</b> ($v - $persent%)";
                }
                print '<p>Всего запросов: <b>' . $all . '</b>. Из них: ' . implode(', ', $ret) . '</p>';
            }
        }
    }
}

function print_graphics($cpuarr, $memfree_arr, $swapfree_arr, $memavailable_arr, $hosts) {
    $period = getPeriod();
    $host_name = getHostNameByReq();
    $date_format = $period > 24 ? 'd.m H:i' : 'H:i';
    ?>
    <script type="text/javascript">
                    google.charts.load('current', {'packages': ['corechart']});
                    //cpu                
                    google.charts.setOnLoadCallback(drawChartCPU);
                    //memory
                    google.charts.setOnLoadCallback(drawChartMem);

                    //request time
                    google.charts.setOnLoadCallback(drawChartReqTime);

                    //request
                    google.charts.setOnLoadCallback(drawChartReq);

                    //one request
                    google.charts.setOnLoadCallback(drawChartOneReq);

                    //one statuses
                    google.charts.setOnLoadCallback(drawChartStatuses);

                    google.charts.setOnLoadCallback(drawChartStatusesOther);

                    function drawChartCPU() {
                        var data = google.visualization.arrayToDataTable([
                            ['Time', 'CPU'],
    <?php
    $cpu_total = 0;

    foreach ($cpuarr as $key => $cpu) {
        $cpu_time = $cpu / 100;
        $cpu_total += $cpu;
        print "['" . gmdate($date_format, $key + 18000) . "', $cpu_time],";
    }
    $cpu_total_time = $cpu_total / 100;
    $cpu_average = round(($cpu_total / (sizeof($cpuarr))) / 100, 2);
    $cpu_title = 'CPU time. Total: ' . $cpu_total_time . '; Average: ' . $cpu_average;
    ?>
                        ]);

                        var options = {
                            title: '<?php print $cpu_title ?>',
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById('cpu_div'));
                        chart.draw(data, options);
                    }
                    
                    function drawChartMem() {
                        var data = google.visualization.arrayToDataTable([
                            ['Time', 'Memory Free', 'Memory Available', 'Swap Free'],
    <?php   
    foreach ($cpuarr as $key => $cpu) {
        $memfree= isset($memfree_arr[$key]) ? $memfree_arr[$key] : 0;
        $swapfree= isset($swapfree_arr[$key]) ? $swapfree_arr[$key] : 0;
        $memavailable = isset($memavailable_arr[$key]) ? $memavailable_arr[$key] : 0;
       
        $total['mem'] += $memfree;
        $total['swap'] += $swapfree;
        $total['memavailable'] += $memavailable;

        print "['" . gmdate($date_format, $key + 18000) . "', $memfree, $memavailable, $swapfree],";
    }
    $mem_pr = round($total['mem'] / sizeof($cpuarr), 0);
    $swap_pr = round($total['swap'] / sizeof($cpuarr), 0);
    $memavailable_pr = round($total['memavailable'] / sizeof($cpuarr), 0);
    $title = 'Memory free avg - ' . $mem_pr . ' Mb. Memory available avg - ' . $memavailable_pr . ' Mb. Swap free avg - ' . $swap_pr.' Mb';
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('mem_div'));
                        chart.draw(data, options);
                    }

                    function drawChartReqTime() {
                        var data = google.visualization.arrayToDataTable([
                            ['Time', 'Total time', 'Bots time', 'Users time'],
    <?php
    $total_time = array();
    foreach ($cpuarr as $key => $cpu) {
        $req_time = isset($hosts[$host_name][$key]['req_time']) ? round($hosts[$host_name][$key]['req_time'], 2) : 0;
        $bots_time = isset($hosts[$host_name][$key]['bots_time']) ? round($hosts[$host_name][$key]['bots_time'], 2) : 0;
        $users_time = $req_time - $bots_time;

        $total_time['req'] += $req_time;
        $total_time['bot'] += $bots_time;
        $total_time['user'] += $users_time;

        print "['" . gmdate($date_format, $key + 18000) . "', $req_time, $bots_time, $users_time],";
    }
    $bots_pr = round(100 * $total_time['bot'] / $total_time['req'], 2);
    $user_pr = round(100 * $total_time['user'] / $total_time['req'], 2);
    $title = 'Request time total - ' . $total_time['req'] . ' sec. Bots time - ' . $total_time['bot'] . ' sec. (' . $bots_pr . '%). User time - ' . $total_time['user'] . ' sec. (' . $user_pr . '%)';
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('reqtime_div'));
                        chart.draw(data, options);
                    }

                    function drawChartReq() {
                        var data = google.visualization.arrayToDataTable([
                            ['Time', 'Total', 'Bots', 'Users'],
    <?php
    $total_req = array();
    foreach ($cpuarr as $key => $cpu) {
        $req_time = isset($hosts[$host_name][$key]['req']) ? $hosts[$host_name][$key]['req'] : 0;
        $bots_time = isset($hosts[$host_name][$key]['bots_req']) ? $hosts[$host_name][$key]['bots_req'] : 0;
        $users_time = $req_time - $bots_time;


        $total_req['req'] += $req_time;
        $total_req['bot'] += $bots_time;
        $total_req['user'] += $users_time;

        print "['" . gmdate($date_format, $key + 18000) . "', $req_time, $bots_time, $users_time],";
    }

    $bots_cpr = round(100 * $total_req['bot'] / $total_req['req'], 2);
    $user_cpr = round(100 * $total_req['user'] / $total_req['req'], 2);
    $title = 'Request total count - ' . $total_req['req'] . '. Bots count - ' . $total_req['bot'] . ' (' . $bots_cpr . '%). Users count - ' . $total_req['user'] . ' (' . $user_cpr . '%)';
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('req_div'));
                        chart.draw(data, options);
                    }

                    function drawChartOneReq() {
                        var data = google.visualization.arrayToDataTable([
                            ['Time', 'Total', 'User', 'Bots'],
    <?php
    $total_one = array();


    foreach ($cpuarr as $key => $cpu) {
        $req = isset($hosts[$host_name][$key]['req']) ? $hosts[$host_name][$key]['req'] : 0;
        $bots = isset($hosts[$host_name][$key]['bots_req']) ? $hosts[$host_name][$key]['bots_req'] : 0;

        $req_time = isset($hosts[$host_name][$key]['req_time']) ? $hosts[$host_name][$key]['req_time'] : 0;
        $bots_time = isset($hosts[$host_name][$key]['bots_time']) ? $hosts[$host_name][$key]['bots_time'] : 0;

        $one_total = $req ? round($req_time / $req, 2) : 0;
        $one_bot = $bots ? round($bots_time / $bots, 2) : 0;

        $user_time = $req_time - $bots_time;
        $user_req = $req - $bots;
        $one_user = $user_req ? round($user_time / $user_req, 2) : 0;

        $total_one['req'] += $one_total;
        $total_one['bot'] += $one_bot;
        $total_one['user'] += $one_user;

        print "['" . gmdate($date_format, $key + 18000) . "', $one_total, $one_user, $one_bot],";
    }

    $one_avg = round($total_one['req'] / sizeof($cpuarr), 2);
    $bots_avg = round($total_one['bot'] / sizeof($cpuarr), 2);
    $user_avg = round($total_one['user'] / sizeof($cpuarr), 2);
    $title = 'One request time. Total avg - ' . $one_avg . ' sec. Bots avg - ' . $bots_avg . ' sec. User avg - ' . $user_avg . ' sec';
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('onereq_div'));
                        chart.draw(data, options);
                    }

                    function drawChartStatuses() {
                        var data = google.visualization.arrayToDataTable([
    <?php
    global $statuses;
    $ret = '';
    $allowSt = array('200', '301', '302');
    if (sizeof($statuses)) {
        $ret .= '[';
        $ret .= "'Time',";
        foreach ($statuses as $st => $cnt) {
            if (in_array($st, $allowSt)) {
                $ret .= "'$st ($cnt)',";
            }
        }
        $ret .= '],';


        foreach ($cpuarr as $key => $cpu) {
            $ret .= "[";
            //time

            $time = gmdate($date_format, $key + 18000);

            $ret .= "'$time',";
            foreach ($statuses as $st => $cnt) {
                if (in_array($st, $allowSt)) {
                    $stval = isset($hosts[$host_name][$key]['status'][$st]) ? $hosts[$host_name][$key]['status'][$st] : 0;
                    $ret .= "$stval,";
                }
            }
            $ret .= "],";
        }
    }
//$ret = str_replace(',]', ']', $ret);
    print $ret;

    $title = 'Satus: ';
    foreach ($statuses as $st => $cnt) {
        if (in_array($st, $allowSt)) {
            $st_pr = round(100 * $cnt / $total_req['req'], 2);
            $title .= "$st ($cnt - $st_pr%).  ";
        }
    }
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('statuses_div'));
                        chart.draw(data, options);
                    }


                    function drawChartStatusesOther() {
                        var data = google.visualization.arrayToDataTable([
    <?php
    global $statuses;
    $ret = '';
    if (sizeof($statuses)) {
        $ret .= '[';
        $ret .= "'Time',";
        foreach ($statuses as $st => $cnt) {
            if (!in_array($st, $allowSt)) {
                $ret .= "'$st ($cnt)',";
            }
        }
        $ret .= '],';


        foreach ($cpuarr as $key => $cpu) {
            $ret .= "[";
            //time

            $time = gmdate($date_format, $key + 18000);

            $ret .= "'$time',";
            foreach ($statuses as $st => $cnt) {
                if (!in_array($st, $allowSt)) {
                    $stval = isset($hosts[$host_name][$key]['status'][$st]) ? $hosts[$host_name][$key]['status'][$st] : 0;
                    $ret .= "$stval,";
                }
            }
            $ret .= "],";
        }
    }
//$ret = str_replace(',]', ']', $ret);
    print $ret;

    $title = 'Satus other: ';
    foreach ($statuses as $st => $cnt) {
        if (!in_array($st, $allowSt)) {
            $st_pr = round(100 * $cnt / $total_req['req'], 2);
            $title .= "$st ($cnt - $st_pr%).  ";
        }
    }
    ?>
                        ]);
                        var options = {
                            title: '<?php print $title ?>',
                        };
                        var chart = new google.visualization.AreaChart(document.getElementById('statuses_other_div'));
                        chart.draw(data, options);
                    }
    </script>

    <div class = "graphics" style = "margin-left:-100px; margin-right:0">
        <div id = "cpu_div" style = "width: 100%; height: 300px;"></div>
        <div id = "mem_div" style = "width: 100%; height: 300px;"></div>
        <div id = "reqtime_div" style = "width: 100%; height: 300px;"></div>
        <div id = "req_div" style = "width: 100%; height: 300px;"></div>
        <div id = "onereq_div" style = "width: 100%; height: 300px;"></div>
        <div id = "statuses_div" style = "width: 100%; height: 300px;"></div>
        <div id = "statuses_other_div" style = "width: 100%; height: 300px;"></div>
    </div>
    <?php
}

function render_host_info_table($name, $info, $cpuarr, $memfree_arr=array(), $swapfree_arr=array()) {
    if (sizeof($info) > 0) {
        
    } else {
        return;
    }

    $keys = array('req', 'ip', 'req_time', 'user_time', 'bots_time', 'user_%', 'bots_%', 'one_req', 'b_sent', 'bots_req', 'bots_ip', 'bots_time', 'bot_req', 'status');
    ?>
    <h2><?php print $name ?></h2>
    <table class="bordered tablesorter">
        <thead>
            <tr>
                <th>Date</th>
                <?php
                if ($name == $host_name) {
                    print '<th>CPU</th>';
                }
                foreach ($keys as $key) {
                    print "<th>$key</th>";
                }

                global $statuses;
                if (sizeof($statuses) > 0) {
                    foreach ($statuses as $key => $value) {
                        print "<th>$key</th>";
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($info as $time => $data) {
                print '<tr>';

                print '<td>' . gmdate('d.m H:i', $time + 18000) . '</td>';

                if ($name == $host_name) {
                    $cpu = isset($cpuarr[$time]) ? $cpuarr[$time] : 0;
                    if ($cpu > 0) {
                        $cpu = $cpu / 100;
                    }
                    print '<td>' . $cpu . '</td>';
                }

                foreach ($keys as $key) {
                    $val = '';
                    if (isset($data[$key])) {
                        $val = $data[$key];
                        if ($key == 'status') {
                            if (sizeof($data[$key]) > 0) {
                                $val = '';
                                arsort($data[$key]);
                                foreach ($data[$key] as $k => $v) {
                                    $val .= "$k:$v, ";
                                }
                                $val = preg_replace('/\, $/', '', $val);
                            }
                        }
                    } else {
                        if ($key == 'one_req') {
                            $val = $data['req'] ? round($data['req_time'] / $data['req'], 3) : 0;
                        } else if ($key == 'bots_%') {
                            $val = $data['req_time'] ? (round($data['bots_time'] / $data['req_time'], 4) * 100) : 0;
                        } else if ($key == 'user_%') {
                            $val = $data['req_time'] ? (round(($data['req_time'] - $data['bots_time']) / $data['req_time'], 4) * 100) : 0;
                        } else if ($key == 'bot_req') {
                            $val = $data['bots_req'] ? round($data['bots_time'] / $data['bots_req'], 3) : 0;
                        } else if ($key == 'user_time') {
                            $val = round($data['req_time'] - $data['bots_time'], 2);
                        }
                    }
                    print '<td>' . $val . '</td>';
                }

                //status
                if (sizeof($statuses) > 0) {
                    foreach ($statuses as $key => $value) {
                        $val = 0;
                        if (sizeof($data['status']) > 0) {
                            if (isset($data['status'][$key])) {
                                $val = $data['status'][$key];
                            }
                        }
                        print '<td>' . $val . '</td>';
                    }
                }
                print '</tr>';
            }
            ?>

            <?php ?>
        </tbody>
    </table>
    <?php
}

function render_host_info($name, $info, $cpuarr) {
    if (sizeof($info) > 0) {
        
    } else {
        return;
    }
    ?>
    <h2><?php print $name ?></h2>
    <table>
        <tbody>     
            <?php if ($name == 'SERVER') { ?>
                <tr>
                    <td>CPU</td>
                    <?php
                    foreach ($info as $key => $value) {
                        $cpu = isset($cpuarr[$key]) ? $cpuarr[$key] : 0;
                        if ($cpu > 0) {
                            $cpu = $cpu / 100;
                        }

                        print '<td>' . $cpu . '</td>';
                    }
                    ?>
                </tr>
            <?php }
            ?>
            <tr>
                <td>Время</td>
                <?php
                foreach ($info as $key => $value) {
                    print '<td>' . gmdate('d.m H:i:s', $key + 18000) . '</td>';
                }
                ?>
            </tr>
            <tr>
                <td>Запросов всего</td>
                <?php
                /* Array
                  (
                  [req] => 2
                  [ip] => 1
                  [status] => Array
                  (
                  [200] => 2
                  )

                  [req_time] => 0.068
                  [b_sent] => 395234
                  [bots_req] => 0
                  [bots_ip] => 1
                  [bots_time] => 0
                  )

                 */

                showInfoValues($info, 'req');
                ?>  
            </tr>
            <tr>
                <td>Ip адреса</td>
                <?php showInfoValues($info, 'ip'); ?>
            </tr>
            <tr>
                <td>Время запросов</td>
                <?php showInfoValues($info, 'req_time'); ?>
            </tr>
            <tr>
                <td>Байт отправленно</td>
                <?php showInfoValues($info, 'b_sent'); ?>                
            </tr>
            <tr>
                <td>Запросы ботов</td>
                <?php showInfoValues($info, 'bots_req'); ?>
            <tr>
                <td>Ip адреса ботов</td>
                <?php showInfoValues($info, 'bots_ip'); ?>
            <tr>
                <td>Время ботов</td>
                <?php showInfoValues($info, 'bots_time'); ?>
            <tr>
                <td>Статус:</td>
                <?php
                foreach ($info as $key => $value) {
                    $valdata = isset($value['status']) ? $value['status'] : array();
                    $val = '';
                    if (sizeof($valdata) > 0) {
                        arsort($valdata);
                        foreach ($valdata as $key => $value) {
                            $val .= "$key:$value";
                            $val .= '<br />';
                        }
                    }
                    print '<td>' . $val . '</td>';
                }
                ?></td>
            </tr>
        </tbody>
    </table>
    <?php
}

function showInfoValues($info, $name) {
    foreach ($info as $key => $value) {
        $val = isset($value[$name]) ? $value[$name] : 0;
        print '<td>' . $val . '</td>';
    }
}

function replace_long_text($text, $len) {
    if (strlen($text) > $len) {
        $text = "<span title=\"$text\">" . substr($text, 0, $len) . "</span>";
    }
    return $text;
}

function p_r($info) {
    print '<pre>';
    print_r($info);
    print '</pre>';
}

Class SafePDO extends PDO {

    public static function exception_handler($exception) {
// Output the exception details
        die('Uncaught exception: ' . $exception->getMessage());
    }

    public function __construct($dsn, $username = '', $password = '', $driver_options = array()) {

// Temporarily change the PHP exception handler while we . . .
        set_exception_handler(array(__CLASS__, 'exception_handler'));

// . . . create a PDO object
        parent::__construct($dsn, $username, $password, $driver_options);

// Change the exception handler back to whatever it was before
        restore_exception_handler();
    }

}
?>
