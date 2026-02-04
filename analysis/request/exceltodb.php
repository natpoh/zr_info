<?php

return;

ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');




include 'db_config.php';
global $pdo;
pdoconnect_db();



global $pdo;
$sql="TRUNCATE TABLE data_movie_rank";
$q = $pdo->prepare($sql);
$q->execute();


function intvalues($val)
{
    $val = str_replace('$','',$val);
    $val = str_replace(',','',$val);
    $val = trim($val);

    if (!$val)$val=0;

    return $val;


}





include $_SERVER['DOCUMENT_ROOT'] . '/analysis/PHPExcel/Classes/PHPExcel.php';





for ($year = 1950; $year <= 2019; $year += 1) {

    $dataFfile = $_SERVER['DOCUMENT_ROOT'] . '/database/scraped_movies/' . $year . '.xls';
    echo $dataFfile;


    if (file_exists($dataFfile)) {
        echo "The file $filename exists";




    $excel = PHPExcel_IOFactory::load($dataFfile);

       $lists=[];

    Foreach ($excel->getWorksheetIterator() as $worksheet) {
        $lists[] = $worksheet->toArray();
    }

    if (is_array($lists)) {

        foreach ($lists as $list) {

            // Перебор строк
            foreach ($list as $row) {

                if ($row[0] != 'Title') {


                    $title = $row[0];

                    $title = preg_replace('/\([0-9]+\)/', '', $title);

                    ///find id
                    $title = trim($title);

                    echo $title . PHP_EOL;


                    $row[0] = $title;


                    $sql = "SELECT MovieID  FROM `data_movie` where `Title` = '" . $title . "' and Year ='" . $year . "' ";

                    /// echo $sql.PHP_EOL;

                    $q = $pdo->prepare($sql);
                    $q->execute();
                    $r = $q->fetch();

                    //  var_dump($r);

                    if ($r['MovieID']) {

                        $MovieID = $r['MovieID'];

                    } else {
                        $MovieID = 0;
                    }


                    $contries_box_arrow_new = [];


                    //   var_dump($row);

                    $contries = $row[2];

                    if ($contries) {
                        $contries_arrow = preg_split('/$\R?^/m', $contries);

                        //     var_dump($contries_arrow);

                        $contries_box = $row[3];
                        $contries_box_arrow = preg_split('/$\R?^/m', $contries_box);


                        //  var_dump($contries_box_arrow);

                        if (is_array($contries_arrow)) {

                            foreach ($contries_arrow as $index => $val) {

                                $bx = $contries_box_arrow[$index];


                                $bx = intvalues($bx);

                                $contries_box_arrow_new[$val] = $bx;
                            }

                            //var_dump($contries_box_arrow_new);

                            $cres = json_encode($contries_arrow);
                            $cresb = json_encode($contries_box_arrow_new);

                            //  echo $cresb.PHP_EOL;

                            //  var_dump($contries_box_arrow_new);

                            $row[2] = $cres;
                            $row[3] = $cresb;
                        }

                    }


                    $row[4] = intvalues($row[4]);
                    $row[5] = intvalues($row[5]);
                    $row[6] = intvalues($row[6]);
                    $row[7] = intvalues($row[7]);
                    $row[8] = intvalues($row[8]);
                    $row[9] = intvalues($row[9]);

                    ///  var_dump($row);

                    global $pdo;
                    $sql = "INSERT INTO `data_movie_rank` VALUES (NULL,'" . $MovieID . "','" . $year . "' ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ?, ?, ?, ? , ?, ? ,'' )";
                    $q = $pdo->prepare($sql);
                    $q->execute($row);


                }
            }

        }

    }
   } else {
    echo "The file $filename does not exist";
}
}
