<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


class CPULOAD{

    public static function getSystemMemInfo() {

        if (function_exists('sys_getloadavg'))
        {
            $load = sys_getloadavg();
            $cpu = round($load[0], 2) * 100;
        }
        else
        {   global $cron_debug;
            if ($cron_debug){echo 'getSystemMemInfo not found';}
        }


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
            'cpu'=>$cpu
        );
        $keys = array_keys($mem_array);
        foreach ($keys as $key) {
            if ($info[$key]) {
                $mem_array[$key] = round($info[$key] / 1000, 0);
            }
        }
        return $mem_array;
    }



    public static function check_load($porcessor=50,$mem=3000,$db=0)
    {
        global $cron_debug;
        $loaded=0;
        $date=0;
        $info='';
        $type='';

        if ($db) {

            $q = "SELECT `cpu`, `date`, `memavailable` FROM `info` ORDER BY `id` desc LIMIT 1";

            $row = Pdo_cpuinfo::db_results_array($q);
            if ($row) {

                if ($cron_debug) {
                    var_dump($row[0]);
                }

                $date = $row[0]['date'];
                $cpu = $row[0]['cpu'];
                $memavailable = $row[0]['memavailable'];
                $type = 'db';
            }
        }
            if (!$db || $date<time()-600)
            {

                $row=self::getSystemMemInfo();

                if ($cron_debug){var_dump($row);}


                if ($row['cpu'] && $row['MemAvailable'])
                {
                    $cpu = $row['cpu'];
                    $memavailable = $row['MemAvailable'];
                    $date=time();

                    $type='current';
                }



                if ($cron_debug){

                    var_dump($row);
                }

            }

            if ($cpu)
            {
                $cpu_load = $cpu/100;
                if ($cpu_load>$porcessor)
                {
                    $loaded =1;
                    $info.=' cpu_load ('.$cpu_load.') > porcessor ('.$porcessor.') ';
                }
            }
        if ($memavailable && $memavailable<$mem)
        {
            $loaded =1;
            $info.=' memavailable ('.$memavailable.') < mem ('.$mem.') ';
        }

        return array('loaded'=>$loaded,'info'=>$info,'cpu'=>$cpu_load,'mem'=>$memavailable,'time'=>date('h:i:s d:m:Y',$date),'type'=>$type);


    }


}

if (isset($_GET['check_load']))
{
    if (isset($_GET['debug']))
    {
        global $cron_debug;
        $cron_debug=1;
    }


    $load = CPULOAD::check_load($_GET['p'],$_GET['m']);
    var_dump($load);
}