<?php
/*
 * Log to file
 */

class FileLog {

    var $log_file = '';

    public function __construct($log_file = './services.log') {
        $this->timer_start();
        $this->log_file = $log_file;
    }

    public function err($msg) {
        $log = '|' . $this->timer_stop(0, 3) . '|' . $msg;
        $this->addEntry("ERROR|$log");
    }

    public function warn($msg) {
        $log = '|' . $this->timer_stop(0, 3) . '|' . $msg;
        $this->addEntry("WARNING|$log");
    }

    public function info($msg) {
        $log = '|' . $this->timer_stop(0, 3) . '|' . $msg;
        $this->addEntry("INFO|$log");
    }

    public function debug($str) {
        $log = '|' . $this->timer_stop(0, 3) . '|' . $msg;
        $this->addEntry("DEBUG|$log");
    }

    function getLogData() {

        $log = $this->log_file;
        $f = fopen($log, "r");
        $result = array();
        if ($f) {

            if (fseek($f, 0, SEEK_END) == 0) {//в конец файла -1 символ перевода строки
                $len = ftell($f);

                $offset = -2;
                $max_len = 5000000;
                $time_offset = 60 * 60; //1 мин.

                $to = 0;
                if ($len > $max_len) {
                    $to = $len - $max_len;
                }

                $time_log_first = '';

                //Ищем начало строки
                $stroka = '';
                for ($i = $len; $i > $to; $i--) {//5000 - предполагаемая макс. длина строки
                    fseek($f, $offset, SEEK_CUR);
                    $read = fread($f, 1);

                    if ($read == "\n") {//если встретился признак конца строки
                        $item = explode("|", $stroka);

                        $time_log = $item[0];
                        $time = strtotime($time_log);

                        if (!$time_log_first) {
                            $time_log_first = $time;
                        } else {
                            if (($time_log_first - $time_offset) > $time) {
                                break;
                            }
                        }
                        //2021-06-08T08:47:10+00:00|INFO||2.905|Not found:https://dove.org/?s=Extremedays| Extremedays

                        $item_names = array(
                            'time_local' => $item[0],
                            'type' => $item[1],
                            'time' => $item[3],
                            'comment' => $item[4],
                            'movie' => $item[5],
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

    function renderLogData($data) {
        if (sizeof($data)) {
            ?>
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Dove service log</title>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                </head>
                <body>
                    <h1>Dove service log</h1>

                    <p><b><?php print sizeof($data) ?></b> rows added in last hour.</p>

                    <?php
                    $infocount = array();

                    foreach ($data as $item) {

                        $infocount[$item['type']] += 1;
                    }

                    print '<p><b>Type</b>: ';
                    arsort($infocount);
                    foreach ($infocount as $key => $val) {
                        print "$key - $val. ";
                    }
                    print '</p>';
                    ?>
                    <style>
                        .word {
                            word-break: break-all;
                        }

                        .table {
                            width: 100%;
                            margin-bottom: 1rem;
                            color: #212529;
                            border-collapse: collapse;
    border-spacing: 0;
                        }

                        .table-bordered {
                            border: 1px solid #dee2e6;
                        }

                        .table-bordered th,
                        .table-bordered td {
                            border: 1px solid #dee2e6;
                        }

                        .table-bordered thead th,
                        .table-bordered thead td {
                            border-bottom-width: 1px;
                        }

                        .table-striped tbody tr:nth-of-type(odd) {
                            background-color: rgba(0, 0, 0, 0.05);
                        }



                    </style>

                    <?php $render_rows = 500; ?>
                    <h3>Show last rows</h3>
                    <table id="dove" class="table table-bordered table-striped">
                        <thead>
                            <tr>

                                <th><?php print 'Date' ?></th>                   
                                <th><?php print 'Type' ?></th>                                                   
                                <th><?php print 'Time' ?></th>                                                   
                                <th><?php print 'Comment' ?></th>    
                                <th><?php print 'Movie' ?></th>  
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 0;
                            foreach ($data as $ip) {
                                if ($i > $render_rows) {
                                    break;
                                }
                                ?>
                                <tr>
                                    <td><?php print date('H:i:s', strtotime($ip['time_local'])); ?></td>
                                    <td><?php print $ip['type']; ?></td>
                                    <td><?php print $ip['time']; ?></td>
                                    <td><?php print $ip['comment']; ?></td>                       
                                    <td><?php print $ip['movie']; ?></td>                                                         
                                </tr>
                                <?php
                                $i++;
                            }
                            ?>
                        </tbody>

                    </table>
                </body>
            </html>
            <?php
        }
    }

    //Api
    private function addEntry($str) {
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
        }
        $handle = fopen($this->log_file, 'a');
        fwrite($handle, sprintf("%s|%s\n", date('c'), $str));
        fclose($handle);
    }

    /**
     * PHP 4 standard microtime start capture.
     *
     * @access private
     * @since 0.71
     * @global int $timestart Seconds and microseconds added together from when function is called.
     * @return bool Always returns true.
     */

    function timer_start() {
        global $timestart;
        $mtime = explode(' ', microtime());
        $timestart = $mtime[1] + $mtime[0];
        return true;
    }


    /**
     * Return and/or display the time from the page start to when function is called.
     *
     * You can get the results and print them by doing:
     * <code>
     * $nTimePageTookToExecute = timer_stop();
     * echo $nTimePageTookToExecute;
     * </code>
     *
     * Or instead, you can do:
     * <code>
     * timer_stop(1);
     * </code>
     * which will do what the above does. If you need the result, you can assign it to a variable, but
     * in most cases, you only need to echo it.
     *
     * @since 0.71
     * @global int $timestart Seconds and microseconds added together from when timer_start() is called
     * @global int $timeend Seconds and microseconds added together from when function is called
     *
     * @param int $display Use '0' or null to not echo anything and 1 to echo the total time
     * @param int $precision The amount of digits from the right of the decimal to display. Default is 3.
     * @return float The "second.microsecond" finished time calculation
     */
    function timer_stop($display = 0, $precision = 3) { // if called like timer_stop(1), will echo $timetotal
        global $timestart, $timeend;
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $timeend = $mtime[1] + $mtime[0];
        $timetotal = $timeend - $timestart;
        $r = ( function_exists('number_format_i18n') ) ? number_format_i18n($timetotal, $precision) : number_format($timetotal, $precision);
        if ($display)
            echo $r;
        return $r;
    }

}
