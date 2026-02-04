<?php
ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors', 'On');


include '../db_config.php';
global $pdo;
pdoconnect_db();
if (!function_exists('normalise_array')) {
function normalise_array($array)
{
    $totalsumm = 0;


    foreach ($array as $index => $val) {
        $totalsumm += $val;
    }
    if ($totalsumm) {
        foreach ($array as $index => $val) {

            $array_result[$index] = round($val * 100 / $totalsumm, 2);


        }

        return $array_result;


    }
    return $array;
}}
if (!function_exists('getCurlCookie')) {
    function getCurlCookie($url, $b = '', $arrayhead = '')
    {
        $cookie_path = ABSPATH . 'cookies/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $proxy = '127.0.0.1:8118';


        // curl_setopt($ch, CURLOPT_PROXY, $proxy);

        if ($arrayhead) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayhead);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);


        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);

        if (strstr($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($b) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $b);

        }
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
function prepare_data_result($result, $country)
{


    if (strpos($result, '/') > 0) {
        $result = substr($result, 0, strpos($result, '/'));

    }
    if (strpos($result, '(') > 0) {
        $result = substr($result, 0, strpos($result, '('));
    }
    if (strpos($result, ')') > 0) {
        $result = substr($result, 0, strpos($result, ')'));
    }
    if (strpos($result, '-') > 0) {
        $result = substr($result, 0, strpos($result, '-'));
    }
    if (strpos($result, ' and') > 0) {
        $result = substr($result, 0, strpos($result, ' and'));
    }

    $result = strtolower($result);

    if (strstr($result, 'indigenous')) {
        $result = 'Indigenous';
    }
    if (strstr($result, 'black') || strstr($result, 'africa')) {
        $result = 'black';
    }
    if (strstr($result, 'native')) {
        $result = 'native';
    }
    if (strstr($result, 'mestizo')) {
        $result = 'latino';
    }

    if (strstr($result, 'european')) {
        $result = 'white';
    }
    if (strstr($result, 'white')) {
        $result = 'white';
    }


    if (strstr($result, 'homogeneous') || strstr($result, 'unknown') || strstr($result, 'other') || strstr($result, 'unspecified')
        || strstr($result, 'foreign') || strstr($result, 'ethnic') || strstr($result, 'response')|| strstr($result, 'not declared')) {
        $result =$country;
    }

    $result = str_replace('predominantly', '', $result);
    $result = str_replace('homogeneous mixture of descendants of', '', $result);
    $result = str_replace('including', '', $result);

    if (strstr(strtolower($result), 'taiwan')) {
        $result = 'Asian';
    }

    if (strstr($result, 'arab')) {
        $result = 'arab';
    }


    $result = trim($result);

    if (strpos($result, 'and') == 0) {
        $result = str_replace('and', '', $result);
    }

    $regv = '#([\w ]+)#';
    $resultdata = '';

    if (preg_match_all($regv, $result, $mach)) {
        foreach ($mach[1] as $val) {
            $resultdata .= $val;
        }
    } else {
        $resultdata = $result;
    }


    if (!$resultdata) {
        $resultdata = $country;
    }
    $resultdata = ucfirst($resultdata);


    return $resultdata;
}

function get_json_file($filename, $type = 1)
{


    global $pdo;


    if ($_GET['clear'] == 1) {
        $sql = "TRUNCATE TABLE data_population_country";
        $q = $pdo->prepare($sql);
        $q->execute();
    }

    $data = file_get_contents($filename);
    $jsondata = json_decode($data);


    if ($type == 1) {
        if (is_array($jsondata)) {
            foreach ($jsondata as $index_country => $data_country) {


                $country_name = $data_country->name->common;
                $country_official = $data_country->name->official;
                $cca2 = $data_country->cca2;
                $cca3 = $data_country->cca3;
                $region = $data_country->region;
                $subregion = $data_country->subregion;
                $latlng = implode(',', $data_country->latlng);
                $area = $data_country->area;
                $zoom = 2000000 / $area;
                if ($zoom > 10) $zoom = 10;
                if ($zoom < 6) $zoom = 6;




                $geolink = '<a target="_blank" href="https://www.google.com/maps/@' . $latlng . ',' . $zoom . 'z">' . $latlng . '</a>';

echo $country_name.' '.$area.' '.$zoom.' '.$geolink.'<br>';



                /// var_dump(  $data_country);

///<iframe src="https://www.google.com/maps/@33,65,6z" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>

                $sql = "SELECT id  FROM `data_population_country`  WHERE `data_population_country`.`cca3` = '" . $cca3 . "'";
                ///echo $sql.PHP_EOL;
                $q = $pdo->prepare($sql);
                $q->execute();
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $r = $q->fetch();

                if ($r['id'] > 0) {
                    // echo $r['id'].PHP_EOL;
                    $sql = "UPDATE `data_population_country` SET 
`country_name` = ? ,
`official` = ? ,
`cca2` = ? ,
`region` = ? ,
`subregion` = ? ,
`latlng` = ? 

WHERE `data_population_country`.`id` = '" . $r['id'] . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($country_name, $country_official, $cca2, $region, $subregion, $geolink));

                    ///   echo $sql.PHP_EOL;
                } else {

                    $sql = "INSERT INTO `data_population_country` VALUES (NULL, '" . $country_name . "','" . $country_official . "', 
                   '" . $cca2 . "','" . $cca3 . "','','','" . $region . "', '" . $subregion . "',    '" . $geolink . "', '','','','')";
                  ///  echo $sql;
                    $q = $pdo->prepare($sql);
                    $q->execute();
                }



            }
        }


    }

    else {
        $array_index = array('name' => 1, 'cca2' => 2, 'area' => 4, 'Density' => 5, 'GrowthRate' => 6, 'WorldPercentage' => 7);


        ///var_dump($jsondata);

        if (is_array($jsondata)) {
            foreach ($jsondata as $index_country => $data_country) {
                $array_pop = [];
                foreach ($data_country as $index => $data) {

                    if (strstr($index, 'pop')) {
                        $index = substr($index, 3);
                        $array_pop[3][$index] = $data*1000;
                    } else {
                        if ($array_index[$index]) {
                            $array_pop[$array_index[$index]] = $data;
                        }
                    }
                }
                $array_pop[3] = json_encode($array_pop[3]);


                $sql = "SELECT id  FROM `data_population_country`  WHERE `data_population_country`.`cca2` = '" . $array_pop[2] . "'";

                ///echo $sql.'<br>';

                $q = $pdo->prepare($sql);
                $q->execute();
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $r = $q->fetch();

                if ($r['id'] > 0) {
                    // echo $r['id'].PHP_EOL;
                    $sql = "UPDATE `data_population_country` SET `population_data` = ? WHERE `data_population_country`.`id` = '" . $r['id'] . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($array_pop[3]));

                     // echo $sql.PHP_EOL;
                }
                else
                {
                    echo 'not id from '.$array_pop[1].' '.$array_pop[2].'<br>';
                }
                /// var_dump($array_pop);
            }
        }
    }

}

function prepare_data($array)
{

    global $pdo;
    //$array = array_slice($array, 0, 11);
    /// var_dump($array) ;
    $country_name = $array[0];
    $country_code = $array[1];
    $yaer = 1960;
    $array_year = [];
    foreach ($array as $index => $data) {
        if ($index > 3) {
            $array_year[$yaer] = $data;
            $yaer++;
        }

    }
    $earstring = json_encode($array_year);

    global $pdo;

    $sql = "SELECT id  FROM `data_population_country`  WHERE `data_population_country`.`cca3` = '" . $country_code . "'";
    ///echo $sql.PHP_EOL;
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $r = $q->fetch();

    if ($r['id'] > 0) {
        // echo $r['id'].PHP_EOL;
        $sql = "UPDATE `data_population_country` SET   `populatin_by_year` = ? WHERE `data_population_country`.`cca3` = '" . $country_code . "'";
        $q = $pdo->prepare($sql);
        $q->execute(array(0 => $earstring));

        ///   echo $sql.PHP_EOL;
    } else {

        ////echo $earstring . '<br><br>';

        $sql = "SELECT id  FROM `data_population_country`  WHERE `data_population_country`.`country_name` = '" . $country_name . "' and cca3 IS NULL";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        $r = $q->fetch();

        if ($r['id'] > 0) {
            // echo $r['id'].PHP_EOL;
            $sql = "UPDATE `data_population_country` SET `populatin_by_year` = ? , cca3 = ? WHERE `data_population_country`.`country_name` = '" . $country_name . "'";
            $q = $pdo->prepare($sql);
            $q->execute(array(0 => $earstring,1=>$country_code));

            ///   echo $sql.PHP_EOL;
        }
        else {

            echo $country_name . ' ' . $country_code . '<br>';

        }




    }


}

function prepare_data_contries_code($array)
{


    //$array = array_slice($array, 0, 11);


    $country_code2 = $array[0];
    $country_name = $array[1];
    $country_code3 = $array[2];


    global $pdo;


    $sql = "SELECT *  FROM `data_population_country`  WHERE `data_population_country`.`cca2` = '" . $country_code2 . "'";
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $r = $q->fetch();

    if ($r['id'] > 0) {
        $sql = "UPDATE `data_population_country` SET `cca3` = ? WHERE `data_population_country`.`cca2` = '" . $country_code2 . "'";
        $q = $pdo->prepare($sql);
        $q->execute(array(0 => $country_code3));

    } else {
        echo $country_name . ' ' . $country_code2 . ' ' . $country_code3 . '<br>';
/*
        $sql = "INSERT INTO `data_population_country` VALUES (NULL, '" . $country_name . "','', '" . $country_code2 . "','" . $country_code3 . "', '', '', '','','','','','','')";
        $q = $pdo->prepare($sql);
        $q->execute();

*/
    }

}

function getlongdatacsv($url, $type)
{
    global $pdo;

    require_once('../PHPExcel/Classes/PHPExcel.php');

    $array_result = array();

    $excel = PHPExcel_IOFactory::load($url);
//var_dump($excel);

    $lists = [];

    Foreach ($excel->getWorksheetIterator() as $worksheet) {
        $lists[] = $worksheet->toArray();
    }

    if (is_array($lists)) {
        foreach ($lists as $list) {
            foreach ($list as $row) {
                $str = $row[0] . $row[1];


                if (!$array_result[$str] && $row[0] != 'CCA2' && $type == 2) {

                    prepare_data_contries_code($row);
                    $array_result[$str] = 1;
                    //  return;
                }


                else if (!$array_result[$str] && $row[0] != 'Data Source' && $row[0] != 'Country Name' && $row[0] != 'Last Updated Date' && $row[0] && $type == 1) {

                    prepare_data($row);
                    $array_result[$str] = 1;
                    //  return;
                }


            }
        }
    }
}


///////country code https://mledoze.github.io/countries/
if ($_GET['1_add_contries_code'] == 1) {
   $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/countries.json';
   get_json_file($filename, 1);
}


////https://www.cia.gov/library/publications/the-world-factbook/fields/400.html
if ($_GET['2_add_ethnic'] == 1) {
    global $pdo;
    $url = 'https://www.cia.gov/library/publications/the-world-factbook/fields/400.html';
    $result = getCurlCookie($url);

    $pos = '<table id="fieldListing"';

    if (strpos($result, $pos)) {
        $result = substr($result, strpos($result, $pos));
    }
    if (strpos($result, '</table>')) {
        $result = substr($result, 0, strpos($result, '</table>'));
    }

    $reg_v = '#\<tr id\=\"([A-Z]+)\"\>[^\>]+\>[^\>]+\>([A-Za-z \-]+)[^\>]+\>[^\>]+\>[^\>]+\>[^\>]+\>[^\>]+\>([^\<]+)\<#';


    $array_code = array('VT' => 'VA', 'BM' => 'MM','BD'=>'BM', 'EZ' => 'CZ','SW' => 'SE','SC'=>'KN','SE'=>'SC','KN'=>'KP',
        'AC'=>'AG','AG'=>'DZ','BG'=>'BD','CH'=>'CN','CN'=>'KM','CG'=>'CD','CF'=>'CG','CK'=>'CC','IV'=>'CI',
        'KS'=>'KR','WZ'=>'SZ','MC'=>'MO','SV'=>'SJ','VQ'=>'VI');

    if (preg_match_all($reg_v, $result, $mach)) {
        foreach ($mach[0] as $i => $data) {


            $data=str_replace('100,000','100000',$data);


            if (preg_match($reg_v, $data, $machinner)) {
                $country_name = trim($machinner[2]);
                $country_code = trim($machinner[1]);
                $country_data = trim($machinner[3]);

                if (strstr($country_name,'Cote d'))
                {
                    $country_name='Cote d Ivoire';
                }



                ///    echo $country_data.PHP_EOL;

               if ($array_code[$country_code]) {
                  $country_code = $array_code[$country_code];
               }

                if (!$country_data) {
                    echo 'not data ' . $country_code . '-' . $country_name . '<br>';
                }



                $sql = "SELECT id  FROM `data_population_country`  WHERE `data_population_country`.`country_name` = '" . $country_name . "'";
                ///echo $sql.PHP_EOL;
                $q = $pdo->prepare($sql);
                $q->execute();
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $r = $q->fetch();

                if ($r['id'] > 0) {
                    // echo $r['id'].PHP_EOL;
                    $sql = "UPDATE `data_population_country` SET `ethnicdata` = ? WHERE `data_population_country`.`country_name` = '" . $country_name . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute(array(0 => $country_data));

                    ///   echo $sql.PHP_EOL;
                }

                else  {

                    $sql = "SELECT *  FROM `data_population_country`  WHERE `data_population_country`.`cca2` = '" . $country_code . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute();
                    $q->setFetchMode(PDO::FETCH_ASSOC);
                    $r = $q->fetch();

                    if ($r['id'] > 0) {
                        //echo $country_code . ' ' . $country_name . ' replace ' . $r['country_name'] . ' <br>';

                        /// echo $r['id'].PHP_EOL;
                        $sql = "UPDATE `data_population_country` SET `ethnicdata` = ? WHERE `data_population_country`.`cca2` = '" . $country_code . "'";
                        $q = $pdo->prepare($sql);
                        $q->execute(array(0 => $country_data));

                        /// echo $sql . PHP_EOL;
                    }
                    else {
                        echo 'not found ' . $country_code . '-' . $country_name . '<br>';
                        ///  echo $country_data.PHP_EOL;

                        $sql = "INSERT INTO `data_population_country` VALUES (NULL, '" . $country_name . "','', '" . $country_code . "','', '', '', '','','',?,'','','')";
                        $q = $pdo->prepare($sql);
                        $q->execute(array(0 => $country_data));
///echo $sql;*/
                    }
                }


            }
        }
    }
    echo 'ok';
//echo $result;

}



if ($_GET['3_add_contries_cc3'] == 1) {

    $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/countries_code.csv';
    getlongdatacsv($filename, 2);
}


//////population https://datatopics.worldbank.org/world-development-indicators/themes/people.html
if ($_GET['4_add_populatin_by_year'] == 1) {
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/world-population-history.csv';

    getlongdatacsv($filename, 1);

}
///////////population forecast https://worldpopulationreview.com/
if ($_GET['add_populatin_future'] == 1) {
    $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/worldpopulation.json';
    get_json_file($filename,2);
}
if (isset($_GET['update_ethnic']) ) {
    $regv = '#([0-9\.\-]+)\%#';

    global $pdo;


    if ($_GET['update_ethnic']==1)
    {
        $sql = "SELECT *  FROM `data_population_country`";
    }
    else
    {

        $sql = "SELECT *  FROM `data_population_country` where `cca2` =  '".$_GET['update_ethnic']."'";
    }


    ///echo $sql.PHP_EOL;
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);

    while ($r = $q->fetch()) {
        $country_id = $r['id'];
        $ethnic = $r['ethnicdata'];
      
        $country = $r['subregion'];
        if (!$country)
        {
            $country = $r['country_name'];
        }
        if ($ethnic) {
            echo '<br><br>' . $ethnic . '<br><br>';


            if (strstr($ethnic, '%')) {
                if (strstr($ethnic, ',')) {

                    $array_ethnic = explode(',', $ethnic);

                    foreach ($array_ethnic as $ethnic_val) {
                        ////  echo $ethnic_val.'<br>';
                        if (preg_match($regv, $ethnic_val, $mach)) {
                            $number = $mach[1];

                            $result = str_replace('%', '', $ethnic_val);
                            $result = trim(str_replace($number, '', $result));

                            $result = prepare_data_result($result, $country);


                            ///   echo $result . '  -  ' . $number . ' <br>';

                            $array_country[$country_id][$result] += $number;
                        }
                    }
                }
            } else {
                if (strstr($ethnic, ',')) {

                    $array_ethnic = explode(',', $ethnic);

                    $count = count($array_ethnic);
                    if (!$count) $count = 1;
                    $percent = round(100 / $count, 2);

                    foreach ($array_ethnic as $result) {


                        $result = prepare_data_result($result, $country);


                        // echo $result .' '.$percent. ' <br>';

                        $array_country[$country_id][$result] += $percent;

                    }
                } else {
                    $result = $ethnic;
                    $result = prepare_data_result($result, $country);
                    if ($result) {
                        $array_country[$country_id][$result] = 100;
                    }
                }
            }
            /// var_dump($array_country[$country_id]);

            ///update data
            ///

            ////clear array


            if ($array_country[$country_id]) {

                $updatedata = json_encode($array_country[$country_id]);


                $sql = "UPDATE `data_population_country` SET `ethnic_array` = ? WHERE `data_population_country`.id = '" . $country_id . "'";

                // echo $sql.'<br>';

                echo $updatedata . '<br>';


                $q2 = $pdo->prepare($sql);
                $q2->execute(array(0 => $updatedata));
            }

        }
    }

/// var_dump($array_country);


}

if ($_GET['5_add_jew'] == 1) {
    global $pdo;
    $url = 'https://en.wikipedia.org/wiki/Jewish_population_by_country';
    $result = getCurlCookie($url);

    $pos = '<table class="nowrap sortable mw-datatable wikitable" style="text-align:right" id="nations">';

    if (strpos($result, $pos)) {
        $result = substr($result, strpos($result, $pos));
    }
    if (strpos($result, '</table>')) {
        $result = substr($result, 0, strpos($result, '</table>'));
    }

    $regv='#\<a[^\>]+\>([a-zA-Z\. ]+)\<\/a\>#';
    $reg_2='#\>([0-9\,]+)\<\/span\>#';

///echo $result;
    $array_total = explode('</tr>',$result);

    function prepare_number($data)
    {
        $data = str_replace('<td style="text-align:right">','',$data) ;
        $data=str_replace(',','',$data) ;
        $data  =trim($data);

        return $data;
    }



    foreach ($array_total as $val)
    {
        $array_country = explode('</td>',$val);
        ///  var_dump($array_country);
///echo $array_country[0];
      ///  echo $array_country[4].'<br>';
        if (preg_match($regv, $array_country[0],$mach)) {


            $array_name = $mach[1];
            $array_core = prepare_number($array_country[1]);
            $array_connected = prepare_number($array_country[4]);
            $array_enlarged = prepare_number($array_country[7]);
            $array_eligible = prepare_number($array_country[10]);
            $array_official = '';
            if (preg_match($reg_2, $array_country[13], $mach)) {
                $array_official = prepare_number($mach[1]);
            }

            if ($array_core > 0) {
                $array_data = array('core' => $array_core, 'connected' => $array_connected, 'enlarged' => $array_enlarged, 'eligible' => $array_eligible, 'official' => $array_official);
                $array_string = json_encode($array_data);

              ///  echo $array_string . PHP_EOL;

                $object_name = array('Czech Republic' => 'Czechia', 'U.S. Virgin Islands' => 'United States Virgin Islands', 'Netherlands Antilles' => 'Caribbean Netherlands');

                if ($object_name[$array_name]) {
                    $array_name = $object_name[$array_name];
                }

                $sql = "SELECT id  FROM `data_population_country` where country_name  ='" . $array_name . "'";
                ///echo $sql.PHP_EOL;
                $q = $pdo->prepare($sql);
                $q->execute();
                $q->setFetchMode(PDO::FETCH_ASSOC);

                $r = $q->fetch();
                if ($r['id']) {
                    $sql = "UPDATE `data_population_country` SET `jew_data` = ? WHERE `data_population_country`.id = '" . $r['id'] . "'";
                    $q2 = $pdo->prepare($sql);
                    $q2->execute(array(0 => $array_string));
                } else {
                    //      echo  $array_name.' '.$array_core.' '.$array_connected.' '.$array_enlarged.' '.$array_eligible.' '.$array_official.'<br>';
                }

            }
        }
        ///   echo '<br><br>';
    }
}




if (isset($_GET['6_update_result_data'] )) {


    global $pdo;



    global $array_compare;
    $array_compare = [];
    $sql = "SELECT * FROM `options` where id =3 limit 1";

    $q = $pdo->prepare($sql);
    $q->execute();
    $r = $q->fetch();


    $val = $r['val'];
    $val = str_replace('\\', '', $val);
    $array_compare_0 = explode("',", $val);
    foreach ($array_compare_0 as $val) {
        $val = trim($val);
        // echo $val.' ';
        $result = explode('=>', $val);
        ///var_dump($result);
        $index = trim(str_replace("'", "", $result[0]));
        $value = trim(str_replace("'", "", $result[1]));

        $regv = '#([A-Za-z\,\(\)\- ]{1,})#';

        if (preg_match($regv, $index, $mach)) {
            $index = $mach[1];
        }


        $index = trim($index);

       // $index = strtolower($index);

        $array_compare[$index] = $value;
    }


    $array_total = [];
    if ($_GET['6_update_result_data']==1)
    {
        $sql = "SELECT *  FROM `data_population_country`";
    }
    else
    {

        $sql = "SELECT *  FROM `data_population_country` where `cca2` =  '".$_GET['6_update_result_data']."'";
    }

    ///echo $sql.PHP_EOL;
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);

    while ($r = $q->fetch()) {
        $array_total=[];
        $ethnic_array = $r['ethnic_array'];
        $country = $r['country_name'];
        $region = $r['subregion'];
        $jew_data  =$r['jew_data'];
        $population =$r['populatin_by_year'];
        $cid =$r['id'];
        if ($jew_data)
        {
            $jew_data= json_decode($jew_data);
            $jew_count=$jew_data->eligible;

            $jew_percent=0;
if ($jew_count>0) {
    if ($population)
    {

       $population= json_decode($population);

        $population=$population->{2018};

        if ($population && $jew_count)
        {
            $jew_percent = ($jew_count/$population)*100;
            $jew_percent = round($jew_percent,2);
        }

    }


}
        }


        if ($ethnic_array) {
            $array_result = json_decode($ethnic_array);
            foreach ($array_result as $index => $val) {

                $index = trim($index);
                $index = strtolower($index);
                $index = ucfirst($index);


                if ($array_compare[$index]) {
                    $index = $array_compare[$index];

                    $array_total[$index] += $val;

                } else {
                    echo $index . ' ' . $country . ' not compare<br>';
                }




            }
        }
        else if ($region)
        {
            if ($array_compare[$region]) {
                $region = $array_compare[$region];
            }


            $array_total[$region] =100;
        }

        arsort($array_total);
        if (is_array($array_total)) {


            $count = 0;
            foreach ($array_total as $i => $v) {
                $count += $v;
                /// echo $i . ' - ' . $v . '<br>';
            }

            if ($count > 101 || $count < 99 && $count > 0) {
                echo '!=100 % ' . $country . '  ' . $count . '<br>';
            }


            if ($count != 100 && $count > 0) {
                echo ' array normalised <br>';
                $array_total = normalise_array($array_total);
            }


            if ($jew_percent > 0.1) {
                ///  echo $country . ' % ' . $jew_percent . '<br>';


                if (($array_total['Jewish'] && $array_total['Jewish'] < $jew_percent) || ($jew_percent && !$array_total['Jewish'])) {

                    $summ = $jew_percent - $array_total['Jewish'];
                    $array_total['Jewish'] = $jew_percent;

                    $key = array_keys($array_total);

                    $array_total[$key[0]] -= $summ;


                }

                $array_total = normalise_array($array_total);


                // print_r($array_total);
                ///     echo '<br><br>';
            }


///////add to db
            if ($array_total) {
                $array_total_string = json_encode($array_total);


                $sql = "UPDATE `data_population_country` SET `ethnic_array_result` = ? WHERE `data_population_country`.id =" . $cid;
                $q2 = $pdo->prepare($sql);
                $q2->execute(array(0 => $array_total_string));

            }
        }


    }



}

///White 	Non-White 	Arab 	Asian 	Black 	Dark Asian 	Indigenous 	Latino 	Mixed / Other 	Jewish (Core) 	Jewish (Law of Return)
///100%	White 62.0,		Arab 1.0%, Asian	3.3%,	Black 12.3%,	3.1%	2.1%	17.3%	2.3%	1.8%	3.8%

//   72.9%	27.1%	    2.3%	5.4%	   3.5%	        8.8%	4.9%	1.3%	1.1%	1.1%	2.0%





