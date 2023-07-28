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
        return date('H:i d-m-Y',$time);
    }

    public static function info($aid)
    {
        $race_small = array(
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

//lastupdate
        $array_rows = ['actor_imdb'=>['name','birth_name','birth_place','burn_date','image_url','image']];

        $adata = self::actor_data($aid);
        $data=[];
        $array_parents=[];

        $data[]=['id'=>'0.0','parent'=>'','name'=>$aid.'<br>('.self::todate($adata['lastupdate']).')'];
        $i =0;
        $array_parents['actor_imdb'] = [];
        foreach ($adata as $row =>$val)
        {
        if (in_array($row,$array_rows['actor_imdb']))
        {

            if ($val)
            {
                if ($row=='image_url'){$val='Y';}

                $res = $row.'<br>'.$val;
            }
            else
            {
                $res = $row.'<br>none';
            }
            $array_parents['actor_imdb'][$row]=$i;
            $data[]=['id'=>'1.'.$i,'parent'=>'0.0','name'=>$res];

        }

         $i+=1;
        }
        ////normalize name

        $name = self::actor_data($aid,'data_actors_normalize','aid');
        $data[]=['id'=>'2.1','parent'=>'1.'.$array_parents['actor_imdb']['name'],'name'=>'normalize:<br>'.$name['firstname'].' '.$name['lastname']];

        ///surname
        $surname = self::actor_data($aid,'data_actors_ethnicolr','aid');
        $data[]=['id'=>'3.1','parent'=>'2.1','name'=>'Surname <br>'.self::todate($surname['date_upd'])];
        $data[]=['id'=>'4.1','parent'=>'3.1','name'=>'verdict:'.$surname['verdict']];

        //familysearch

        $familysearch = self::actor_data($name['lastname'],'data_familysearch_verdict','lastname');

        $data[]=['id'=>'3.2','parent'=>'2.1','name'=>'Familysearch<br>'.self::todate($familysearch['last_upd'])];
        $data[]=['id'=>'4.2','parent'=>'3.2','name'=>'name:'.$familysearch['lastname']];
        $data[]=['id'=>'4.3','parent'=>'3.2','name'=>'verdict:'.$race_small[$familysearch['verdict']]];


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
console.log(data);
        </script>

        <?php
    }


}