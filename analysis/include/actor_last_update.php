<?php session_start();
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';


class ActorsInfo{



    private static  function generateShade($color = '')
    {


        if (empty($color)) {

            return self::generateRandomBrightColor();
        }


        $red = hexdec(substr($color, 1, 2));
        $green = hexdec(substr($color, 3, 2));
        $blue = hexdec(substr($color, 5, 2));

        $change1 = rand(-200, 150);
        $change2 = rand(-200, 150);

        if ($red >= $green && $red >= $blue) {
            $newRed = $red;
            $newGreen = max(0, min(255, $green + $change1));
            $newBlue = max(0, min(255, $blue + $change2));
        } elseif ($green >= $red && $green >= $blue) {
            $newRed = max(0, min(255, $red + $change1));
            $newGreen = $green;
            $newBlue = max(0, min(255, $blue + $change2));
        } else {
            $newRed = max(0, min(255, $red + $change1));
            $newGreen = max(0, min(255, $green + $change2));
            $newBlue = $blue;
        }

        $newShade = sprintf("#%02x%02x%02x", $newRed, $newGreen, $newBlue);


        while (in_array($newShade, $_SESSION['shades'] ?? [])) {
            $newShade =  self::generateRandomBrightColor();
        }

        if ($color)
        {
        ///    echo '<div><span style="background-color: '.$color.'">'.$color.'</span>=><span style="background-color: '.$newShade.'">'.$newShade.'</span></div>';
        }

        return $newShade;
    }

    private static    function generateRandomBrightColor()
    {
        $red = rand(200, 255);
        $green = rand(200, 255);
        $blue = rand(200, 255);

        return sprintf("#%02x%02x%02x", $red, $green, $blue);
    }







    private static function actor_data($aid,$db='data_actors_imdb',$val='id')
    {
        $q = "SELECT * FROM `".$db."` WHERE `".$val."` = '".($aid)."' LIMIT 1";
        $r = Pdo_an::db_results_array($q);
        return $r[0];
    }

    private static function todate($time)
    {
        if (!$time)
        {
            return 'Never';
        }
        return date('d-m-Y',$time);
    }

    private static function structure_array($array_actors)
    {
       // var_dump($array_actors);


        $array_colors = [];
        $gray_color='#cccccc';
        $colors_rows=[];

        $nodes=[];
        $links=[];

        foreach ($array_actors as $from=>$data)
        {
         if ($data['color'])
        {
            $color=$data['color'];
        }
           else  if ( $colors_rows[$from])
            {
                $color=self::generateShade($colors_rows[$from]);
            }
            else
            {
                $color=self::generateShade();
            }


            if (!$data['enable'])
            {
                $data['color'] =$gray_color;
            }
            else
            {
                $data['color'] =$color;
            }





            if ($data['sub'])
            {
                foreach ($data['sub'] as $subnames)
                {




                  // ["White", "Grey", 1, "White", "Grey", "",'#ff0000'],

                    if (is_array($subnames))
                    {
                        $line_width=$subnames[1];
                        $subnames=$subnames[0];
                    }
                    else
                    {
                        $line_width=10;
                    }

                    $content = $array_actors[$subnames]['content'];

                    $colors_rows[$subnames]=$color;


                    if (!$array_actors[$subnames]['enable'])
                    {

                        $links[]=[$from,$subnames,$line_width,$subnames,$content,'',$gray_color];
                    }
                    else
                    {
                        $links[]=[$from,$subnames,$line_width,$subnames,$content,''];
                    }

                }

            }


           // {"id":"White","name":"White","surname":"White","images":1,"date_birth":"actors","url":"https://example.com/actor1","status":"active","lastupdate":"2023-07-28","desc":"Actor description 1"}

            $tonodes = $data;
            $tonodes['id']=$from;
            $nodes[]=$tonodes;




           // echo '<div style="background-color: '.$color.'">'.$color.': '.$from.'</div>';
        }






            return [$nodes,$links];
    }

    private static function arrayToTable($data,$row='') {
        $table = "<table class='styled-table'>";
        $table .= "<tr><th>Field</th><th>Value</th></tr>";

        foreach ($data as $field => $value) {

               if ($row && $row ==$field)
               {
                   $table .= "<tr class='highlighted'><td>$field</td><td>$value</td></tr>";
               }

           else
           {
               $table .= "<tr><td>$field</td><td>$value</td></tr>";
           }


        }

        $table .= "</table>";

        return $table;
    }

    private static function to_content($adata,$only_enable='',$sub='',$color='')
    {
        $adata_r=$adata;
        $enable=0;
        if ($adata_r)
        {
            $enable =1;
        }
        if ($only_enable)
        {
            if ($enable)
            {
                $adata_r='Y';
            }
            else
            {
                $adata_r='';
            }
        }
        if (!$adata_r)$adata_r='Null';

        if (!$color)$color= self::generateShade();

        if ($sub)
        {
            return ['enable'=>$enable,'content'=>$adata_r,'sub'=>$sub,'color'=>$color,'desc'=>$adata];
        }
        else
        {
            return ['enable'=>$enable,'content'=>$adata_r,'color'=>$color];
        }


    }
    private static function check_enable($data,$row='')
    {
        $enable ='';
        if ($row)
        {
        if ($data[$row])$enable=1;
        }
        else if ($data)$enable=1;

        return $enable;
    }

    private static function array_to_content($data,$title,$desc,$show_tabe='',$sub='',$time='',$content='',$row='',$color='')
    {
        $array=[];

        $array=[
            'name'=>$title,
            'enable'=>self::check_enable($data,$row),
               ];

        $array['content']=$content;


        if ($color) $array['color']=$color;
        if ($desc) $array['desc']=$desc;
        if ($show_tabe && $data) $array['desc'].='<br>'.self::arrayToTable($data,$row);

        if ($sub) $array['sub']=$sub;
        if ($time) $array['time']=self::todate($data[$time]);
     return $array;

    }

    private static function link_db($table)
    {
        $link = WP_SITEURL.'/analysis/data.php?onlytable='.$table;

        return '<a target="_blank" href="'.$link.'">'.$table.'</a>';


    }


    public static function info($aid)
    {


        $rsm = array(
            1 =>'W',
            2 => 'EA',
            3 =>'H',
            4 =>'B',
            5 => 'I',
            6 => 'M' ,
            7 => 'MIX',
            8 =>'JW' ,
            9 => 'IND',
        );
        $acc = array('Sadly, not'  => 'N/A','1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A','W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');

        $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');
        $array_convert_auto = array('1' => 'Male', '2' => 'Female', '0' => 'NA');
        $array_convert_imdb = array('m' => 'Male', 'f' => 'Female');


        $adata = self::actor_data($aid);


        $data=[];
        $array_parents=[];

        ////normalize name

        $name = self::actor_data($aid,'data_actors_normalize','aid');

        $source_name= $name['source_name'];
        $source_name_birth_name=null;
        $source_name_name=null;

        if ($source_name==2)
        {
            $source_name_birth_name=['data_actors_normalize'];
        }
        else
        {
            $source_name_name=['data_actors_normalize'];
        }


        $array_actors = [$aid=>['name'=>'Actor ID  '.$aid,  'sub'=>[['actor_imdb',30],['imdb_gender',5],['actors_tmdb',2],['actors_crowd',2]] ,'color'=>'#008afe','enable'=>1],
                'actor_imdb'=>[
                    'name'=>'Actor IMDb '.self::todate($adata['lastupdate']),
                    'content'=>'',
                    'color'=>'#a58aff',
                    'time'=>self::todate($adata['lastupdate']),
                    'desc'=>'db = '.self::link_db('data_actors_imdb').'<br><a target="_blank" href="/analysis/include/scrap_imdb.php?add_empty_actors='.$aid.'&debug=1">update data</a><br>'.self::arrayToTable($adata),
                    'enable'=>self::check_enable($adata),


            'sub'=> ['image_url','image','name','birth_name',['birth_place',3],['burn_date',3]]
        ],

            'image_url'=>self::to_content($adata['image_url'],1,['image_download'],'#ff0000'),
            'image'=>self::to_content($adata['image'],'',['image_download'],'#ff0000'),

           'name'=>self::to_content($adata['name'],'',$source_name_name,'#00ff00'),
            'birth_name'=>self::to_content($adata['birth_name'],'',$source_name_birth_name,'#02af02'),


        ];







        ///actor meta
        $actors_meta = self::actor_data($aid,'data_actors_meta','actor_id');


        $img_enable=0;
        $img_number = str_pad($aid, 7, '0', STR_PAD_LEFT);
        $imgsource =ABSPATH.'analysis/img_final/'.$img_number.'.jpg';
        $img_content='not load';
        if (file_exists($imgsource)) {$img_enable=1; $img_content ='<img style="width:200px" src="/analysis/img_final/'.$img_number.'.jpg">';}
        $array_actors['image_download'] = self::array_to_content([],'Image download','<a target="_blank" href="/analysis/include/scrap_imdb.php?check_image_on_server='. $aid.'">update</a></br>'.$img_content,0,['kairos','bettaface']);
        $array_actors['image_download']['enable']=$img_enable;

        //kairos
        $kairos = self::actor_data($aid,'data_actors_race','actor_id');

        $array_actors['kairos']=self::array_to_content($kairos,'Kairos: '. self::todate($kairos['last_update']),'db: '.self::link_db('data_actors_race').' ',1,['kairos_verdict'],'last_update');
        $array_actors['kairos_verdict']=self::array_to_content($actors_meta,'Kairos Verdict: '. $acc[$rsm[$actors_meta['n_kairos']]],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update',$kairos['kairos_verdict'],'n_kairos');

        //bettaface
        $bettaface = self::actor_data($aid,'data_actors_face','actor_id');

        $array_actors['bettaface']=self::array_to_content($bettaface,'Bettaface: '. self::todate($bettaface['last_update']),'db: '.self::link_db('data_actors_face').' <br><a target="_blank" href="/analysis/include/scrap_imdb.php?check_face='.$aid.'&debug=1">update</a>',1,['bettaface_verdict'],'last_update');
        $array_actors['bettaface_verdict']=self::array_to_content($actors_meta,'bettaface Verdict: '. $acc[$rsm[$actors_meta['n_bettaface']]],'db: '.self::link_db('data_actors_meta').'  ',1,[['verdict',2]],'last_update',$bettaface['race'],'n_bettaface');

        $array_actors['data_actors_normalize']=self::array_to_content($name,'Normalize: '.$name['firstname'].' '.$name['lastname'].' '. self::todate($name['last_upd']),'db: '.self::link_db('data_actors_normalize').' ',1,['familysearch',['familysearch_verdict',2],'forebears',['forebears_verdict',2],'surname','ethnic',['auto_gender',4]]);


        ///surname
        $surname = self::actor_data($aid,'data_actors_ethnicolr','aid');
        $array_actors['surname']=self::array_to_content($surname,'Surname: '. self::todate($surname['date_upd']),'db: '.self::link_db('data_actors_ethnicolr').' ',1,['surname_verdict'],'date_upd');
        $array_actors['surname_verdict']=self::array_to_content($actors_meta,'Surname Verdict: '. $acc[$rsm[$actors_meta['n_surname']]],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update',$acc[$surname['verdict']],'n_surname');


        //familysearch
        $familysearch = self::actor_data($name['lastname'],'data_familysearch_verdict','lastname');
        $array_actors['familysearch']=self::array_to_content($familysearch,'Familysearch: '. self::todate($familysearch['last_upd']),'db: '.self::link_db('data_familysearch_verdict').' ',1,['familysearch_verdict'],'last_upd',$familysearch['lastname']);
        $array_actors['familysearch_verdict']=self::array_to_content($actors_meta,'Familysearch Verdict: '. $acc[$rsm[$actors_meta['n_familysearch']]],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update',$acc[$rsm[$familysearch['verdict']]],'n_familysearch');

        //forebears
        $forebears = self::actor_data($name['lastname'],'data_forebears_verdict','lastname');
        $array_actors['forebears']=self::array_to_content($forebears,'Forebears: '. self::todate($forebears['last_upd']),'db: '.self::link_db('data_forebears_verdict').' ',1,['forebears_verdict'],'last_upd',$forebears['lastname']);
        $array_actors['forebears_verdict']=self::array_to_content($actors_meta,'Forebears Verdict: '. $acc[$rsm[$actors_meta['n_forebears_rank']]],'db: '.self::link_db('data_actors_meta').' ' ,1,[['verdict',2]],'last_update',$acc[$rsm[$forebears['verdict_rank']]],'n_forebears_rank');

        ///ethnic

        $array_actors['birth_place'] =self::to_content($adata['birth_place'],1,[['ethnic',1]]);
        $array_actors['burn_date'] =self::to_content($adata['burn_date'],'',[['ethnic',1]]);


        $ethnic = self::actor_data($aid,'data_actors_ethnic','actor_id');
        $array_actors['ethnic']=self::array_to_content($ethnic,'Ethnicelebs: '. self::todate($ethnic['last_update']),'db: '.self::link_db('data_actors_ethnic').' <br><a target="_blank" href="/analysis/include/scrap_imdb.php?set_actors_ethnic='.$aid.'&debug=1">update</a>',1,['ethnic_verdict'],'last_update','','',self::generateShade('#00ff00'));
        $array_actors['ethnic_verdict']=self::array_to_content($actors_meta,'Ethnicelebs Verdict: '. $acc[$rsm[$actors_meta['n_ethnic']]],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update',$ethnic['verdict'],'n_ethnic');




        ///gender

       $data_actors_gender = self::actor_data($aid,'data_actors_gender','actor_id');
       $array_actors['imdb_gender']=self::array_to_content($data_actors_gender,'Gender IMDb ','db: '.self::link_db('data_actors_gender').' ',1,[['gender_verdict',3]],'',$array_convert_imdb[$data_actors_gender['Gender']],'','#aaddff');


        $data_actor_gender_auto = self::actor_data($aid,'data_actor_gender_auto','actor_id');
        $array_actors['auto_gender']=self::array_to_content($data_actor_gender_auto,'Gender auto','db: '.self::link_db('data_actor_gender_auto').' ',1,[['gender_verdict',4]],'',$array_convert_auto[$data_actor_gender_auto['gender']]);

        $array_actors['gender_verdict']=self::array_to_content($actors_meta,'Gender Verdict: '. $array_convert[$actors_meta['gender']],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update','','gender');

        $array_actors['verdict']=self::array_to_content($actors_meta,'Verdict: '. $acc[$rsm[$actors_meta['n_verdict_weight']]].' '.$array_convert[$actors_meta['gender']],'db: '.self::link_db('data_actors_meta').' ',1,'','last_update','','n_verdict_weight');


        ///tmdb
        $data_actors_tmdb = self::actor_data($aid,'data_actors_tmdb','actor_id');
        $array_actors['actors_tmdb']=self::array_to_content($data_actors_tmdb,'Actor TMDB ','db: '.self::link_db('data_actors_tmdb').' ',1,[['tmdb_image_download',4]],'last_update','','','#ffcc22');
        //tmdb images


        $img_enable=0;
        $imgsource =ABSPATH.'analysis/img_final_tmdb/'.$img_number.'.jpg';
        $img_content='not load';
        if (file_exists($imgsource)) {$img_enable=1; $img_content ='<img style="width:200px" src="/analysis/img_final_tmdb/'.$img_number.'.jpg">';}
        $array_actors['tmdb_image_download'] = self::array_to_content([],'Image download','<a target="_blank" href="/analysis/include/scrap_imdb.php?check_tmdb_image_on_server='. $aid.'">update</a></br>'.$img_content,0,[['tmdb_kairos',4]]);
        $array_actors['tmdb_image_download']['enable']=$img_enable;


        //tmdb kairos
        $tmdb_kairos = self::actor_data($aid,'data_actors_tmdb_race','actor_id');

        $array_actors['tmdb_kairos']=self::array_to_content($tmdb_kairos,'Kairos: '. self::todate($tmdb_kairos['last_update']),'db: '.self::link_db('data_actors_tmdb_race').' ',1,[['kairos_verdict',2]],'last_update');

        //tmdb bettaface
       // $bettaface = self::actor_data($aid,'data_actors_face','actor_id');
       // $array_actors['bettaface']=self::array_to_content($bettaface,'Bettaface: '. self::todate($bettaface['last_update']),'db: '.self::link_db('data_actors_face').' <br><a target="_blank" href="/analysis/include/scrap_imdb.php?check_face='.$aid.'&debug=1">update</a>',1,['bettaface_verdict'],'last_update');


        ///crowd
        $data_actors_crowd = self::actor_data($aid,'data_actors_crowd','actor_id');
        $array_actors['actors_crowd']=self::array_to_content($data_actors_crowd,'Actor Crowd ','db: '.self::link_db('data_actors_crowd').' ',1,[['crowd_image_download',4],['crowd_verdict',4]],'add_time','','','#aaff22');

        $img_enable=0;
        $imgsource =ABSPATH.'analysis/img_final_crowd/'.$img_number.'.jpg';
        $img_content='not load';
        if (file_exists($imgsource)) {$img_enable=1; $img_content ='<img style="width:200px" src="/analysis/img_final_crowd/'.$img_number.'.jpg">';}
        $array_actors['crowd_image_download'] = self::array_to_content([],'Image download','<a target="_blank" href="/analysis/include/scrap_imdb.php?download_crowd_images='. $aid.'">update</a></br>'.$img_content,0,[['crowd_kairos',4]]);
        $array_actors['crowd_image_download']['enable']=$img_enable;


        //crowd kairos
        $crowd_kairos = self::actor_data($aid,'data_actors_crowd_race','actor_id');

        $array_actors['crowd_kairos']=self::array_to_content($crowd_kairos,'Kairos: '. self::todate($crowd_kairos['last_update']),'db: '.self::link_db('data_actors_crowd_race').' ',1,[['kairos_verdict',2]],'last_update');

        $array_actors['crowd_verdict']=self::array_to_content($actors_meta,'Crowd Verdict: '. $acc[$rsm[$actors_meta['n_crowdsource']]],'db: '.self::link_db('data_actors_meta').' ',1,[['verdict',2]],'last_update',$acc[$data_actors_crowd['verdict']],'n_crowdsource');




        [$nodes,$links]  = self::structure_array($array_actors);











        ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title<?php echo $adata['name'] ?></title>
  <!-- Include the Highcharts library -->
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script src="https://code.highcharts.com/modules/sankey.js"></script>
  <style>
    /* Custom CSS for the popup */
    .popup-container {
      position: absolute;
      background-color: #fff;
      border: 1px solid #ccc;
      padding: 10px;
      z-index: 9999;
        display: none;
        max-width: 80vw;
    }
    .styled-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #ddd;
        font-size: 16px;
    }

    .styled-table th, .styled-table td {
        padding: 10px;
        text-align: left;
        white-space: break-spaces;
        word-break: break-all;
        min-width: 100px;
    }

    .styled-table th {
        background-color: #f2f2f2;
    }

    .styled-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .styled-table tr:hover {
        background-color: #ddd;
    }

    .styled-table tr.highlighted {
        background-color: #98ffa4;
    }
        .img_container{
            text-align: center;
        }
    .img_container img{
        width: 326px;
        height: auto;
    }
  </style>
</head>
<body>
<div style="display: flex;gap: 20px"><a href="https://info.antiwoketomatoes.com/analysis/include/scrap_imdb.php?actor_logs=<?php echo $aid ?>">Server</a><a href="https://zeitgeistreviews.com/analysis/include/scrap_imdb.php?actor_logs=<?php echo $aid ?>">ZR</a></div>
<div class="popup-container"></div>
  <div id="container" style="min-width: 300px; height:1000px; margin: 0 auto;"></div>
<div class="img_container" ><img src="https://info.antiwoketomatoes.com/analysis/create_image/<?php echo $aid.'_v'.time(); ?>.jpg"></div>

  <script>

      function enableDraggablePopup() {
          // ѕолучаем ссылку на popup контейнер
          const popupContainer = document.querySelector('.popup-container');

          // ѕеременные дл€ отслеживани€ состо€ни€ перетаскивани€
          let isDragging = false;
          let offset = { x: 0, y: 0 };

          // ‘ункци€ дл€ начала перетаскивани€
          function startDrag(e) {
              isDragging = true;

              // ¬ычисл€ем смещение между координатами мыши и положением контейнера
              const rect = popupContainer.getBoundingClientRect();
              offset = {
                  x: e.clientX - rect.left,
                  y: e.clientY - rect.top
              };
          }

          // ‘ункци€ дл€ окончани€ перетаскивани€
          function stopDrag() {
              isDragging = false;
          }

          // ‘ункци€ дл€ обновлени€ положени€ контейнера при перетаскивании
          function drag(e) {
              if (isDragging) {
                  // ѕолучаем новые координаты мыши и обновл€ем положение контейнера
                  const x = e.clientX - offset.x;
                  const y = e.clientY - offset.y;
                  const maxX = window.innerWidth - popupContainer.offsetWidth;
                  const maxY = window.innerHeight - popupContainer.offsetHeight;

                  // ќграничиваем положение контейнера внутри границ диспле€
                  const newX = Math.max(0, Math.min(x, maxX));
                  const newY = Math.max(0, Math.min(y, maxY));

                  // ”станавливаем новое положение контейнера
                  popupContainer.style.left = newX + 'px';
                  popupContainer.style.top = newY + 'px';
              }
          }

          // ƒобавл€ем обработчики событий дл€ перетаскивани€
          popupContainer.addEventListener('mousedown', startDrag);
          document.addEventListener('mouseup', stopDrag);
          document.addEventListener('mousemove', drag);
      }


    document.addEventListener('DOMContentLoaded', function() {
      let popupVisible = false; // Track if the popup is visible


        Highcharts.chart('container', {
                 chart: {
                    inverted: true,

                },
        title: {
          text: '<?php echo $adata['name'] ?>'
        },
        accessibility: {
          point: {
            valueDescriptionFormat: '{index}. {point.from} to {point.to}, {point.weight}.'
          }
        },
        series: [
          {
              keys: ['from', 'to', 'weight', 'name', 'desc', 'lastupdate','color'],
            data:   <?php  echo json_encode($links); ?>
                //["White", "Grey", 1, "White", "Grey", "",'#ff0000'],
              //  ["White", "Cryan", 1, "White", "Cryan", "2023-07-27"],
            ,
            type: 'sankey',
            name: '',
              dataLabels: {
                  enabled: true,
                  style: {
                      fontSize: '12px',
                  },
                  formatter: function() {
                      return  this.point.desc + '<br>' +
                          this.point.lastupdate;
                  }
              },
            linkOpacity: 0.5,
            nodes: <?php  echo json_encode($nodes);?>
          }
        ],
        plotOptions: {
          sankey: {
            point: {
              events: {
                click: function () {

                  // Toggle the visibility of the popup on click
                  if (popupVisible) {
                    hidePopup();
                  } else {

                    showPopup(this.tooltipPos[0], this.tooltipPos[1], this);
                  }
                }
              }
            }
          }
        }
      });

      // Function to show the popup
      function showPopup(x, y, nodeData) {


        if (!popupVisible) {
          const popupContent = `
            <div class="popup-container-inner">
              <b>${nodeData.name}</b><br>
              Status: ${nodeData.enable}<br>
              Last Update: ${nodeData.time}<br>
              Description: ${nodeData.desc}<br>
             </div>
          `;

          const popup = document.querySelector('.popup-container');
          popup.innerHTML = popupContent;
          popup.style.left = x + 'px';
          popup.style.top = y + 'px';
          popupVisible = true;
            popup.style.display = "block";
          // Add a click event to the document to hide the popup when clicking outside it
            enableDraggablePopup();

            setTimeout(function() {
           document.addEventListener('click', hidePopupOnClick);
                 }, 200);
        }

      }

      // Function to hide the popup
      function hidePopup() {

        const popup = document.querySelector('.popup-container');
        if (popup) {
            popup.style.display = "none";
        }
        popupVisible = false;

        // Remove the click event to hide the popup when clicking outside it
        document.removeEventListener('click', hidePopupOnClick);
      }

      // Function to hide the popup when clicking outside it
      function hidePopupOnClick(event) {

        if (!event.target.closest('.popup-container')) {
          hidePopup();
        }
      }
    });
  </script>
</body>
</html>
            <?php

    }


}