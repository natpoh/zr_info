<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';


class ActorsInfo{

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
        $gray_color='#cccccc';

        $nodes=[];
        $links=[];

        foreach ($array_actors as $from=>$data)
        {
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

    private static function to_content($adata,$only_enable='',$sub='')
    {
        $enable=0;
        if ($adata)
        {
            $enable =1;
        }
        if ($only_enable)
        {
            if ($enable)
            {
                $adata='Y';
            }
            else
            {
                $adata='';
            }
        }
        if (!$adata)$adata='Null';

        if ($sub)
        {
            return ['enable'=>$enable,'content'=>$adata,'sub'=>$sub];
        }
        else
        {
            return ['enable'=>$enable,'content'=>$adata];
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

    private static function array_to_content($data,$title,$desc,$show_tabe='',$sub='',$time='',$content='',$row='')
    {
        $array=[];

        $array=[
            'name'=>$title,
            'enable'=>self::check_enable($data,$row),
               ];

        $array['content']=$content;
        if ($desc) $array['desc']=$desc;
        if ($show_tabe && $data) $array['desc'].='<br>'.self::arrayToTable($data,$row);

        if ($sub) $array['sub']=$sub;
        if ($time) $array['time']=self::todate($data[$time]);
     return $array;

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


        $array_actors = [$aid=>['name'=>'Actor ID  '.$aid,
                'sub'=>['actor_imdb']],
                'actor_imdb'=>[
                    'name'=>'Actor IMDb '.self::todate($adata['lastupdate']),
                    'content'=>'',
                    'time'=>self::todate($adata['lastupdate']),
                    'desc'=>'db = actor_imdb<br>'.self::arrayToTable($adata),
                    'enable'=>self::check_enable($adata),


            'sub'=> [['birth_place',3],['burn_date',3],'name','birth_name','image_url','image']
        ],
           'name'=>self::to_content($adata['name'],'',$source_name_name),
            'birth_name'=>self::to_content($adata['birth_name'],'',$source_name_birth_name),
            'birth_place'=>self::to_content($adata['birth_place'],1,[['ethnic',1]]),'burn_date'=>self::to_content($adata['burn_date'],'',[['ethnic',1]])
            ,'image_url'=>self::to_content($adata['image_url'],1,['image_download']),'image'=>self::to_content($adata['image'],'',['image_download'])
        ];






      $array_actors['data_actors_normalize']=self::array_to_content($name,'Normalize:'. self::todate($name['last_upd']),'db: data_actors_normalize',1,['ethnic','familysearch',['familysearch_verdict',2],'forebears',['forebears_verdict',2],'surname']);

        ///actor meta
        $actors_meta = self::actor_data($aid,'data_actors_meta','actor_id');



        ///surname
        $surname = self::actor_data($aid,'data_actors_ethnicolr','aid');
        $array_actors['surname']=self::array_to_content($surname,'Surname: '. self::todate($surname['date_upd']),'db: data_actors_ethnicolr',1,['surname_verdict'],'date_upd');
        $array_actors['surname_verdict']=self::array_to_content($actors_meta,'Surname Verdict: '. $acc[$rsm[$actors_meta['n_surname']]],'db: data_actors_meta',1,'','last_update',$acc[$surname['verdict']],'n_surname');


        //familysearch
        $familysearch = self::actor_data($name['lastname'],'data_familysearch_verdict','lastname');
        $array_actors['familysearch']=self::array_to_content($familysearch,'Familysearch: '. self::todate($familysearch['last_upd']),'db: data_familysearch_verdict',1,['familysearch_verdict'],'last_upd',$familysearch['lastname']);
        $array_actors['familysearch_verdict']=self::array_to_content($actors_meta,'Familysearch Verdict: '. $acc[$rsm[$actors_meta['n_familysearch']]],'db: data_actors_meta',1,'','last_update',$acc[$rsm[$familysearch['verdict']]],'n_familysearch');

        //forebears
        $forebears = self::actor_data($name['lastname'],'data_forebears_verdict','lastname');
        $array_actors['forebears']=self::array_to_content($forebears,'Forebears: '. self::todate($forebears['last_upd']),'db: data_forebears_verdict',1,['forebears_verdict'],'last_upd',$forebears['lastname']);
        $array_actors['forebears_verdict']=self::array_to_content($actors_meta,'Forebears Verdict: '. $acc[$rsm[$actors_meta['n_forebears_rank']]],'db: data_actors_meta',1,'','last_update',$acc[$rsm[$forebears['verdict']]],'n_forebears_rank');

        ///ethnic

        $ethnic = self::actor_data($aid,'data_actors_ethnic','actor_id');
        $array_actors['ethnic']=self::array_to_content($ethnic,'Ethnicelebs: '. self::todate($ethnic['last_update']),'db: data_actors_ethnic',1,['ethnic_verdict'],'last_update');
        $array_actors['ethnic_verdict']=self::array_to_content($actors_meta,'Ethnicelebs Verdict: '. $acc[$rsm[$actors_meta['n_ethnic']]],'db: data_actors_meta',1,'','last_update',$ethnic['verdict'],'n_ethnic');



        $array_actors['image_download'] = self::array_to_content([],'Image: ','',0,['kairos']);
        //kairos
        $kairos = self::actor_data($aid,'data_actors_race','actor_id');

        $array_actors['kairos']=self::array_to_content($kairos,'Kairos: '. self::todate($kairos['last_update']),'db: data_actors_race',1,['kairos_verdict'],'last_update');
        $array_actors['kairos_verdict']=self::array_to_content($actors_meta,'Kairos Verdict: '. $acc[$rsm[$actors_meta['n_kairos']]],'db: data_actors_meta',1,'','last_update',$kairos['kairos_verdict'],'n_kairos');


        [$nodes,$links]  = self::structure_array($array_actors);











        ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Highcharts Sankey Diagram</title>
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


  </style>
</head>
<body>
<div class="popup-container"></div>
  <div id="container" style="min-width: 300px; height:1000px; margin: 0 auto;"></div>

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
                    marginBottom: 100
                },
        title: {
          text: 'Highcharts Sankey Diagram'
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
            name: 'Sankey demo series',
        //  colors: ['#939393', '#00ff00', '#4ca8de'],
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


        return;
        ?>


        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/modules/treemap.js"></script>
        <script src="https://code.highcharts.com/modules/treegraph.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/accessibility.js"></script>

        <div id="container"></div>

        <script type="text/javascript">
            const data = <?php echo json_encode($data)?>;
            const data2 = [
                {
                    id: '0.0',
                    parent: '',
                    name: 'The Actor<br>(26.06.2023)'
                },

                {
                    id: '1.1',
                    parent: '0.0',
                    name: 'Name'
                },
                {
                    id: '1.2',
                    parent: '0.0',
                    name: 'Birth Name'
                },
                {
                    id: '1.3',
                    parent: '0.0',
                    name: 'Image'
                },
                {
                    id: '1.4',
                    parent: '0.0',
                    name: 'Birth date'
                },
                {
                    id: '1.5',
                    parent: '0.0',
                    name: 'Birth place'
                },
                {
                    id: '1.6',
                    parent: '0.0',
                    name: 'Actor meta'
                },
                {
                    id: '2.1',
                    parent: '1.1',
                    name: 'Surname'
                },

                {
                    id: '2.2',
                    parent: '1.1',
                    name: 'Western Africa'
                },

                {
                    id: '2.3',
                    parent: '1.1',
                    name: 'North Africa'
                },

                {
                    id: '2.2',
                    parent: '1.1',
                    name: 'Central Africa'
                },

                {
                    id: '2.4',
                    parent: '1.1',
                    name: 'South America'
                },

                /* America */
                {
                    id: '2.9',
                    parent: '1.2',
                    name: 'South America'
                },

                {
                    id: '2.8',
                    parent: '1.2',
                    name: 'Northern America'
                },

                {
                    id: '2.7',
                    parent: '1.2',
                    name: 'Central America'
                },

                {
                    id: '2.6',
                    parent: '1.2',
                    name: 'Caribbean'
                },

                /* Asia */
                {
                    id: '2.13',
                    parent: '1.3',
                    name: 'Southern Asia'
                },

                {
                    id: '2.11',
                    parent: '1.3',
                    name: 'Eastern Asia'
                },

                {
                    id: '2.12',
                    parent: '1.3',
                    name: 'South-Eastern Asia'
                },

                {
                    id: '2.14',
                    parent: '1.3',
                    name: 'Western Asia'
                },

                {
                    id: '2.10',
                    parent: '1.3',
                    name: 'Central Asia'
                },

                /* Europe */
                {
                    id: '2.15',
                    parent: '1.4',
                    name: 'Eastern Europe'
                },

                {
                    id: '2.16',
                    parent: '1.4',
                    name: 'Northern Europe'
                },

                {
                    id: '2.17',
                    parent: '1.4',
                    name: 'Southern Europe'
                },

                {
                    id: '2.18',
                    parent: '1.4',
                    name: 'Western Europe'
                },
                /* Oceania */
                {
                    id: '2.19',
                    parent: '1.4',
                    name: 'Australia and New Zealand'
                },

                {
                    id: '2.20',
                    parent: '1.5',
                    name: 'Melanesia'
                },

                {
                    id: '2.21',
                    parent: '1.5',
                    name: 'Micronesia'
                },

                {
                    id: '2.22',
                    parent: '1.5',
                    name: 'Polynesia'
                }
            ];

            Highcharts.chart('container', {
                chart: {
                    inverted: true,
                    marginBottom: 170
                },
                title: {
                    text: 'Actor info',
                    align: 'left'
                },
                series: [
                    {
                        type: 'treegraph',
                        data,
                        tooltip: {
                            pointFormat: '{point.name}'
                        },
                        dataLabels: {
                            pointFormat: '{point.name}',
                            style: {
                                whiteSpace: 'nowrap',
                                color: '#000000',
                                textOutline: '3px contrast'
                            },
                            crop: false
                        },
                        marker: {
                            radius: 6
                        },
                        levels: [
                            // {
                            //     level: 1,
                            //     dataLabels: {
                            //         align: 'left',
                            //         x: 20
                            //     }
                            // },
                            // {
                            //     level: 2,
                            //     colorByPoint: true,
                            //     dataLabels: {
                            //         verticalAlign: 'bottom',
                            //         y: -20
                            //     }
                            // },
                            // {
                            //     level: 3,
                            //     colorVariation: {
                            //         key: 'brightness',
                            //         to: -0.5
                            //     },
                            //     dataLabels: {
                            //         align: 'left',
                            //         rotation: 90,
                            //         y: 20
                            //     }
                            // }
                        ]
                    }
                ]
            });


        </script>

        <?php
    }


}