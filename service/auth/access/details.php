<?php
$log = '/var/log/nginx/access.log';
//$log = 'logtest/access.log';  //Для тестов

$data = getLogData($log);



renderLogData($data);

function renderLogData($data) {

    if (sizeof($data) && isset($data['result']) && sizeof($data['result'])) {
        $result = $data['result'];
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
                </style>
                <title>Nginx access time details</title>
            </head>
            <body>
                                <p>
                    <a href="index.php">Access</a> | 
                    <a href="slow.php">Slow</a> | 
                    <a href="time.php">Time</a> | 
                    <b><a href="details.php">Details</a></b> 
                </p>
                <h1>Nginx access time details. From <?php print date('d.m.Y H:i:s', $data['start']) ?> to <?php print date('d.m.Y H:i:s', $data['end']) ?></h1>
                <?php
                if (sizeof($result) > 0) {
                    ksort($result);
                    ?>

                    <table class="tablesorter">
                        <thead>
                            <tr>
                                <th>Last call</th>
                                <th>Path</th>
                                <th>Total seconds</th>
                                <th>Total time</th>
                                <th>Requests</th>
                                <th>One request</th>
                                <th>Max request time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $key => $value) { 
                                if ($value['sec']>=1){
                                ?>
                                <tr>
                                    <td><?php print date('d.m.Y H:i:s', $value['time']); ?></td>
                                    <td><?php print replace_long_text($key,100) ?></td>
                                    <td><?php print round($value['sec'], 2) ?></td>
                                    <td><?php print ((int) gmdate('d', round($value['sec'], 0)) - 1) . "d," ?> <?php print gmdate('H:i:s', round($value['sec'], 0)) ?></td>
                                    <td><?php print $value['req'] ?></td>
                                    <td><?php print round($value['sec'] / $value['req'], 2) ?></td>
                                    <td><?php print round($value['maxreq'], 2) ?></td>
                                </tr>
                            <?php 
                                }
                            } ?>
                        </tbody>
                    </table>
                <?php } ?>
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

    $ret = array();


    $f = fopen($log, "r");
    $result = array();
    if ($f) {

        if (fseek($f, 0, SEEK_END) == 0) {//в конец файла -1 символ перевода строки
            $len = ftell($f);

            $offset = -2;
            $max_len = 10000000; //Сколько символов читать
            $time_offset = 60 * 60 * 24; //24 часа.

            $time_log_end = '';
            $time_log_start = '';

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



                    if (!$time_log_end) {
                        $time_log_end = $time;
                    }

                    /* Log data
                      '$proxy_add_x_forwarded_for|$time_local|'
                      '$status|$request_length|$bytes_sent|$request_time|'
                      '$request|$http_referer|$http_user_agent';
                     */
                    $item_names = array(
                        'ip' => $item[0],
                        'time_local' => strtotime($item[1]),
                        'status' => $item[2],
                        'request_length' => $item[3],
                        'bytes_sent' => $item[4],
                        'request_time' => $item[5],
                        'request' => $item[6],
                        'http_referer' => $item[7],
                        'http_user_agent' => $item[8],
                        'host' => $item[9],
                    );
                    $request = $item_names['request'];
                    if (strstr($request, '?')){
                       $reqarr = explode('?', $request); 
                       $request = $reqarr[0];
                    }

                    $req_key = $item_names['host'] . " " . $request;

                    if ($item_names['request_time'] > 0) {

                        $result[$req_key]['sec']+=(float) $item_names['request_time'];
                        $result[$req_key]['req']+=1;
                        $result[$req_key]['time'] = $item_names['time_local'];


                        if (!isset($result[$req_key]['maxreq']) || $result[$req_key]['maxreq'] < $item_names['request_time']) {

                            $result[$req_key]['maxreq'] = (float) $item_names['request_time'];
                        }
                    }

                    if ($time) {
                        if (!$time_log_first) {
                            $time_log_first = $time;
                        } else {

                            if (($time_log_first - $time_offset) > $time) {

                                break;
                            }
                        }
                    }

                    //  $result[] = $item_names;
                    $stroka = '';
                } else {
                    $stroka = $read . $stroka;
                }
            }
            $time_log_start = $time;
            $ret = array('start' => $time_log_start, 'end' => $time_log_end, 'result' => $result);
        }

        fclose($f);
    }
    return $ret;
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
    );

    return $bots;
}
?>
