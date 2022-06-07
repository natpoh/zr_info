<?php
$p = 'agd2e2SDsdf3d3sl';
$pass = $_GET['p'];

if ($pass !== $p) {
    die();
}

$log = '/var/log/nginx/access.log';
//$log = 'logtest/access.log';  //Для тестов

$data = getLogData($log);



renderLogData($data);

/*
  [ip] => 68.180.228.125
  [time_local] => 12/Oct/2015:19:55:55 +0300
  [status] => 200
  [request_length] => 195
  [bytes_sent] => 41435
  [request_time] => 0.633
  [request] => GET /category/army/page/26/ HTTP/1.1
  [http_referer] => -
  [http_user_agent] => Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp
  [host]
 */

function renderLogData($data) {
    if (sizeof($data)) {
        $bots = useronline_get_bots_custom();

        $time_first = strtotime($data[0]['time_local']);
        $time_end = strtotime($data[sizeof($data) - 1]['time_local']);
        $host = array();
        $def_host = array('req' => 0, 'ip' => array(), 'status' => array(), 'req_time' => 0, 'b_sent' => 0, 'bots_req' => 0, 'bots_ip' => array(), 'bots_time' => 0);

        foreach ($data as $item) {

            //SERVER
            if (!isset($host['SERVER'])) {
                $host['SERVER'] = $def_host;
            }

            $host['SERVER']['req'] += 1;

            $host['SERVER']['ip'][$item['ip']] += 1;

            $host['SERVER']['status'][$item['status']] += 1;
            $host['SERVER']['req_time'] += $item['request_time'];
            $host['SERVER']['b_sent'] += $item['bytes_sent'];

            //host   
            if (!isset($host['host'])) {
                $host['host'] = $def_host;
            }
            $host[$item['host']]['req'] += 1;
            $host[$item['host']]['ip'][$item['ip']] += 1;
            $host[$item['host']]['status'][$item['status']] += 1;
            $host[$item['host']]['req_time'] += $item['request_time'];
            $host[$item['host']]['b_sent'] += $item['bytes_sent'];




            // Check For Bot
            foreach ($bots as $name => $lookfor) {
                if (stristr($item['http_user_agent'], $lookfor) !== false) {

                    $host['SERVER']['bots_req'] += 1;
                    $host['SERVER']['bots_ip'][$item['ip']] += 1;
                    $host['SERVER']['bots_time'] += $item['request_time'];

                    $host[$item['host']]['bots_req'] += 1;
                    $host[$item['host']]['bots_ip'][$item['ip']] += 1;
                    $host[$item['host']]['bots_time'] += $item['request_time'];

                    break;
                }
            }
        }

        //Убираем IP адреса
        foreach ($host as $name => $info) {
            $ip_count = isset($info['ip']) ? sizeof($info['ip']) : 0;
            $host[$name]['ip'] = $ip_count;

            $bot_count = isset($info['bots_ip']) ? sizeof($info['bots_ip']) : 0;
            $host[$name]['bots_ip'] = $bot_count;

            $host[$name]['req_time'] = '' . $host[$name]['req_time'];
            $host[$name]['bots_time'] = '' . $host[$name]['bots_time'];
        }

        //CpuLoad
        $load = sys_getloadavg();
        $cpu = round($load[0], 2) * 100;

        //Запуск крон
        $show = $_GET['show'];
        if (!$show) {

            include 'mode.php';



            if ($pdo_connect_data) {
                // p_r($cpu);
                //Сохраняем данные в базу
                $dbhost = 'mysql:host=' . $pdo_connect_data['host'] . ';dbname=' . $pdo_connect_data['db'];
                $dbh = new SafePDO($dbhost, $pdo_connect_data['user'], $pdo_connect_data['pass']);

                if ($_GET['install_info']) {
                    install_info($dbh);
                }

                $infostr = serialize($host);
                $date = $time_first;

                $mem_info = getSystemMemInfo();

                //Добавляем значения в таблицу
                $query = sprintf("INSERT INTO info (cpu,date,info,memtotal,memfree,buffers,cached,dirty,slab,swaptotal,swapfree,memavailable) "
                        . "VALUES ('%d','%d','%s','%d','%d','%d','%d','%d','%d','%d','%d','%d')", $cpu, $date, $infostr, $mem_info['MemTotal'], $mem_info['MemFree'], $mem_info['Buffers'], $mem_info['Cached'], $mem_info['Dirty'], $mem_info['Slab'], $mem_info['SwapTotal'], $mem_info['SwapFree'], $mem_info['MemAvailable']);

                $sth = $dbh->query($query);

                // соединение больше не нужно, закрываем
                $sth = null;
                $dbh = null;
            }
        } else {
            ?>
            <!DOCTYPE HTML>
            <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

                    </style>
                    <title>Server info</title>
                </head>
                <body>
                    <h1>Информация о системе</h1>
                    <p>Период <?php print date('d.m.y H:i:s', $time_first); ?> — <?php print date('d.m.y H:i:s', $time_end); ?></p>
                    <p>CPU <?php print $cpu; ?></p>                    
                    <p>Память<br />
                    <pre><?php
            $mem_info = getSystemMemInfo();
            print_r($mem_info);
            ?></pre>
                </p><?php
            //Хосты
            if ($host) {
                if (sizeof($host) > 0) {
                    ksort($host);
                    //   p_r($host);
                    //Выводим данные
                    $host_names = array();
                    foreach ($host as $name => $info) {
                        $host_names[] = $name;
                    }
                    print '<p>Hosts: ' . implode(', ', $host_names) . '</p>';

                    foreach ($host as $name => $info) {
                        render_host_info($name, $info);
                    }
                }
            }
            ?>


            </body>
            </html>
            <?php
        }
    }
}

function getSystemMemInfo() {
    $data = file_get_contents("/proc/meminfo");
    preg_match_all('/(\w+):\s+(\d+)\s/', $data, $matches);
    $info = array_combine($matches[1], $matches[2]);
    $mem_array = array(
        'MemTotal' => 0,
        'MemFree' => 0,
        'Buffers' => 0,
        'Cached' => 0,
        'Dirty' => 0,
        'Slab' => 0,
        'SwapTotal' => 0,
        'SwapFree' => 0,
        'MemAvailable' => 0,
    );
    $keys = array_keys($mem_array);
    foreach ($keys as $key) {
        if ($info[$key]) {
            $mem_array[$key] = round($info[$key] / 1000, 0);
        }
    }
    return $mem_array;
}

function render_host_info($name, $info) {
    /*    [pandoraopen.ru] => Array
      (
      [req] => 304
      [ip] => 100
      [status] => Array
      (
      [200] => 275
      [302] => 19
      [301] => 9
      [304] => 1
      )

      [req_time] => 91.904
      [bots_req] => 161
      [bots_ip] => 22
      [bots_time] => 45.19
      ) */
    ?>
    <h2><?php print $name ?></h2>
    <table>
        <thead>
            <tr>
                <th>Показатель</th>
                <th>Значение</th>
            </tr>
        </thead>
        <tbody>           
            <tr><td>Запросов всего</td><td><?php print isset($info['req']) ? $info['req'] : 0  ?></td></tr>
            <tr><td>Ip адреса</td><td><?php print isset($info['ip']) ? $info['ip'] : 0  ?></td></tr>
            <tr><td>Время запросов</td><td><?php print isset($info['req_time']) ? $info['req_time'] : 0  ?></td></tr>
            <tr><td>Байт отправленно</td><td><?php print isset($info['b_sent']) ? $info['b_sent'] : 0  ?></td></tr>
            <tr><td>Запросы ботов</td><td><?php print isset($info['bots_req']) ? $info['bots_req'] : 0  ?></td></tr>
            <tr><td>Ip адреса ботов</td><td><?php print isset($info['bots_ip']) ? $info['bots_ip'] : 0  ?></td></tr>
            <tr><td>Время ботов</td><td><?php print isset($info['bots_time']) ? $info['bots_time'] : 0  ?></td></tr>
            <tr><td>Статус:</td>
                <td><?php
    if ($info['status']) {
        arsort($info['status']);
        foreach ($info['status'] as $key => $value) {
            print "$key:$value";
            print '<br />';
        }
    }
    ?></td>
            </tr>
        </tbody>
    </table>
    <?php
}

function replace_long_text($text, $len) {
    if (strlen($text) > $len) {
        $text = "<span title=\"$text\">" . substr($text, 0, $len) . "</span>";
    }
    return $text;
}

function getLogData($log) {




    $f = fopen($log, "r");
    $result = array();
    if ($f) {

        if (fseek($f, 0, SEEK_END) == 0) {//в конец файла -1 символ перевода строки
            $len = ftell($f);

            $offset = -2;
            $max_len = 5000000;
            $time_offset = 60; //60 сек.



            $time_log_first = '';

            //Ищим начало строки
            $stroka = '';
            for ($i = $len; $i > ($len - $max_len); $i--) {//5000 - предполагаемая макс. длина строки
                $seec = fseek($f, $offset, SEEK_CUR);
                if ($seec == -1) {
                    break;
                }

                $read = fread($f, 1);

                if ($read == "\n") {//если встретился признак конца строки
                    $item = explode("|", $stroka);

                    $time_log = $item[1];
                    $time = strtotime($time_log);

                    if (!$time_log_first) {
                        $time_log_first = $time;
                    } else {
                        //print "$time_log_first $time<br />";
                        if (($time_log_first - $time_offset) > $time) {
                            break;
                        }
                    }

                    /* Log data
                      '$proxy_add_x_forwarded_for|$time_local|'
                      '$status|$request_length|$bytes_sent|$request_time|'
                      '$request|$http_referer|$http_user_agent';
                     */
                    $host = isset($item[9]) ? $item[9] : 'unknow';
                    $item_names = array(
                        'ip' => $item[0],
                        'time_local' => $item[1],
                        'status' => $item[2],
                        'request_length' => $item[3],
                        'bytes_sent' => $item[4],
                        'request_time' => $item[5],
                        'request' => $item[6],
                        'http_referer' => $item[7],
                        'http_user_agent' => $item[8],
                        'host' => $host,
                    );
                    $result[] = $item_names;
                    $stroka = '';
                } else {
                    $stroka = $read . $stroka;
                }
            }
        }

        fclose($f);
    }
    return $result;
}

function useronline_get_bots_custom() {
    $bots = array(
        'Googlebot' => 'Googlebot',
        'Google Bot' => 'Google Bot',
        'Googlebot-News' => 'Googlebot-News',
        'Googlebot-Image' => 'Googlebot-Image',
        'Googlebot-Video' => 'Googlebot-Video',
        'Googlebot-Mobile' => 'Googlebot-Mobile',
        'Mediapartners-Google' => 'Mediapartners-Google',
        'AdsBot-Google' => 'AdsBot-Google',
        'google' => 'Google',
        'MSN' => 'msnbot',
        'BingBot' => 'bingbot',
        'Alex' => 'ia_archiver',
        'Lycos' => 'lycos',
        'Ask Jeeves' => 'jeeves',
        'Altavista' => 'scooter',
        'AllTheWeb' => 'fast-webcrawler',
        'Inktomi' => 'slurp@inktomi',
        'Turnitin.com' => 'turnitinbot',
        'Technorati' => 'technorati',
        'Yahoo' => 'yahoo',
        'Findexa' => 'findexa',
        'NextLinks' => 'findlinks',
        'Gais' => 'gaisbo',
        'WiseNut' => 'zyborg',
        'WhoisSource' => 'surveybot',
        'Bloglines' => 'bloglines',
        'BlogSearch' => 'blogsearch',
        'PubSub' => 'pubsub',
        'Syndic8' => 'syndic8',
        'RadioUserland' => 'userland',
        'Gigabot' => 'gigabot',
        'Become.com' => 'become.com',
        'Baidu' => 'baidu',
        'Yandex' => 'yandex',
        'Rambler' => 'Rambler',
        'Mail.Ru' => 'Mail.Ru',
        'Webalta' => 'Webalta',
        'Quintura' => 'Quintura-Crw',
        'Turtle' => 'TurtleScanner',
        'Webfind' => 'webfind',
        'Aport' => 'Aport',
        'Amazon' => 'amazonaws.com',
        'Twitterbot' => 'Twitterbot',
        'applebot' => 'applebot',
        'AhrefsBot' => 'AhrefsBot',
        'SemrushBot' => 'SemrushBot',
    );

    return $bots;
}

if (!function_exists('p_r')) {

    function p_r($array) {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }

}

function coreInfoSleep() {

    /* get core information (snapshot) */
    $stat1 = GetCoreInformation();
    /* sleep on server for one second */
    sleep(1);
    /* take second snapshot */
    $stat2 = GetCoreInformation();
    /* get the cpu percentage based off two snapshots */
    $data = GetCpuPercentages($stat1, $stat2);

    /* ouput pretty images */
    foreach ($data as $k => $v) {
        echo '<img src="' . makeImageUrl($k, $v) . '" />';
    }
}

/* Gets individual core information */

function GetCoreInformation() {
    $data = file('/proc/stat');
    $cores = array();
    //  p_r($data);
    foreach ($data as $line) {
        if (preg_match('/^cpu[0-9]/', $line)) {
            $info = explode(' ', $line);
            $cores[] = array(
                'user' => $info[1],
                'nice' => $info[2],
                'sys' => $info[3],
                'idle' => $info[4]
            );
        }
    }
    return $cores;
}

/* compares two information snapshots and returns the cpu percentage */

function GetCpuPercentages($stat1, $stat2) {
    if (count($stat1) !== count($stat2)) {
        return;
    }
    $cpus = array();
    for ($i = 0, $l = count($stat1); $i < $l; $i++) {
        $dif = array();
        $dif['user'] = $stat2[$i]['user'] - $stat1[$i]['user'];
        $dif['nice'] = $stat2[$i]['nice'] - $stat1[$i]['nice'];
        $dif['sys'] = $stat2[$i]['sys'] - $stat1[$i]['sys'];
        $dif['idle'] = $stat2[$i]['idle'] - $stat1[$i]['idle'];
        $total = array_sum($dif);
        $cpu = array();
        foreach ($dif as $x => $y)
            $cpu[$x] = round($y / $total * 100, 1);
        $cpus['cpu' . $i] = $cpu;
    }
    return $cpus;
}

/* makes a google image chart url */

function makeImageUrl($title, $data) {
    $url = 'http://chart.apis.google.com/chart?chs=440x240&cht=pc&chco=0062FF|498049|F2CAEC|D7D784&chd=t:';
    $url .= $data['user'] . ',';
    $url .= $data['nice'] . ',';
    $url .= $data['sys'] . ',';
    $url .= $data['idle'];
    $url .= '&chdl=User|Nice|Sys|Idle&chdlp=b&chl=';
    $url .= $data['user'] . '%25|';
    $url .= $data['nice'] . '%25|';
    $url .= $data['sys'] . '%25|';
    $url .= $data['idle'] . '%25';
    $url .= '&chtt=Core+' . $title;
    return $url;
}

function install_info($dbh) {
    $sql = "CREATE TABLE IF NOT EXISTS  `info`(
				`id` int(11) unsigned NOT NULL auto_increment,				
				`cpu` int(11) NOT NULL DEFAULT '0',	
                                `date` int(11) NOT NULL DEFAULT '0',	
                                `info` text default NULL,                                                                                 
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE utf8mb4_general_ci;";
    $sth = $dbh->query($sql);

    $sql = "ALTER TABLE `info` ADD `memtotal` int(11) NOT NULL DEFAULT '0';"
            . " ALTER TABLE `info` ADD `memfree` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `buffers` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `cached` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `dirty` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `slab` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `swaptotal` int(11) NOT NULL DEFAULT '0'; "
            . " ALTER TABLE `info` ADD `swapfree` int(11) NOT NULL DEFAULT '0'; ";
    $sth = $dbh->query($sql);
    
    $sql = "ALTER TABLE `info` ADD  `memavailable` int(11) NOT NULL DEFAULT '0';";
    $sth = $dbh->query($sql);        

    return $sth;
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
