<?php
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

        $unique_ip = array();
        foreach ($data as &$item) {
            if ($item['status'] == '400') {
                continue;
            }

            $ip = $item['ip'];

            if (!$unique_ip[$ip]) {
                $unique_ip[$ip] = $item;
            }
            $unique_ip[$ip]['count'] +=1;
            $unique_ip[$ip]['bytes_sent'] += $item['bytes_sent'];
            $unique_ip[$ip]['request_time'] += $item['request_time'];
        }

        $data = $unique_ip;


        $masks = array();

        $host_count = array();
        $status_count = array();
        $host_req_time = array();

        $bots_count = 0;
        foreach ($data as &$item) {

            //host
            $host_count[$item['host']]+=1;
            $status_count[$item['status']]+=1;
            $host_req_time[$item['host']]+=$item['request_time'];

            //ip mask
            $ip = $item['ip'];
            if (strstr(',', $ip)) {
                $ips = explode(', ', $ip);
                $ip = $ips[1];
            }
            $ip_arr = explode('.', $ip);
            $ip_mask = $ip_arr[0] . "." . $ip_arr[1] . '.' . $ip_arr[2];
            $item['mask'] = $ip_mask;
            $masks[$ip_mask]+=1;


            // Check For Bot
            $bot_found = 'none';
            $type = 'guest';

            foreach ($bots as $name => $lookfor) {
                if (stristr($item['http_user_agent'], $lookfor) !== false) {
                    $bot_found = $name;
                    $type = 'bot';
                    $bots_count+=1;
                    break;
                }
            }

            $item['name'] = $bot_found;
            $item['type'] = $type;
        }
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

                <style>
                    body {
                        display: block;
                        margin: 8px;
                    }

                    td .word {
                        min-width: 150px;
                    }
                    .word {word-break: break-all;}
                </style>
                <title>Total online log</title>
            </head>
            <body>
                <?php include 'menu.php' ?>
                <h1>Total online <?php print sizeof($unique_ip) ?>. Bots <?php print $bots_count ?></h1>
                <?php
                if ($host_count) {
                    arsort($host_count);
                    print '<p> Host count: ';
                    foreach ($host_count as $key => $value) {
                        print "$key:$value. ";
                    }
                    print '</p>';
                }
                                if ($host_req_time) {
                    arsort($host_req_time);
                    print '<p> Host total request time: ';
                    foreach ($host_req_time as $key => $value) {
                        print "$key:$value. ";
                    }
                    print '</p>';
                }
                if ($status_count) {
                    arsort($status_count);
                    print '<p> Status count: ';
                    foreach ($status_count as $key => $value) {
                        print "$key:$value. ";
                    }
                    print '</p>';
                }
                ?>
                <table id="usersonline" class="bordered tablesorter">
                    <thead>
                        <tr>
                            <th><?php print '№' ?></th>
                            <th><?php print 'time' ?></th>
                            <th><?php print 'host' ?></th>   
                            <th><?php print 'count' ?></th>
                            <th><?php print 'ip mask' ?></th>
                            <th><?php print 'mask count' ?></th>
                            <th><?php print 'ip' ?></th>
                            <th><?php print 'name' ?></th>                    
                            <th><?php print 'type' ?></th>  
                            <th><?php print 'status' ?></th>
                            <th><?php print 'request length' ?></th>
                            <th><?php print 'bytes sent' ?></th>
                            <th><?php print 'request time' ?></th>
                            <th><?php print 'request' ?></th>
                            <th><?php print 'http referer' ?></th>
                            <th><?php print 'http user agent' ?></th>                    

                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                         */

                        $n = 1;
                        foreach ($data as $ip) {
                            ?>
                            <tr>
                                <td><?php print $n; ?></td>
                                <td><?php print date('d.m.Y H:i:s', strtotime($ip['time_local'])); ?></td>
                                <td><?php print $ip['host']; ?></td>
                                <td><?php print $ip['count']; ?></td>
                                <td><?php print $ip['mask']; ?></td>
                                <td><?php print $masks[$ip['mask']]; ?></td>                        
                                <td><?php print $ip['ip']; ?></td>  
                                <td><?php print $ip['name']; ?></td>
                                <td><?php print $ip['type']; ?></td>
                                <td><?php print $ip['status']; ?></td>
                                <td><?php print $ip['request_length']; ?></td>
                                <td><?php print $ip['bytes_sent']; ?></td>
                                <td><?php print $ip['request_time']; ?></td>
                                <td><p class="word"><?php print replace_long_text($ip['request'], 100); ?></p></td>
                                <td><p class="word"><?php print replace_long_text($ip['http_referer'], 100); ?></p></td>
                                <td><p class="word"><?php print replace_long_text($ip['http_user_agent'], 100); ?></p></td>

                            </tr>
                            <?php
                            $n++;
                        }
                        ?>
                    </tbody>

                </table>
            </body>
        </html>
        <?php
    }
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
                        'host' => $item[9],
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
?>
