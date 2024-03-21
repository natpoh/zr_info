<?php

/**
 * @license Apache 2.0
 */

namespace OpenApi\Fd\Models;

use OpenApi\Fd\Controllers\Controller;

/**
 * Class Order.
 *
 * @author  Brahmnan <brahmnan@gmail.com>
 *
 * @OA\Schema(
 *     title="Media model",
 *     description="Media model",
 * )
 */
class Media extends Model {

    /**
     * @OA\Property(
     *     default=0,
     *     example=21055,
     *     format="int64",
     *     title="ID",
     *     description="ID",
     * )
     *
     * @var int
     */
    public $id;

    /**
     * @OA\Property(
     *     default="",
     *     example="Movie",
     *     title="Type.",
     *     description="Type.",
     *     enum={"Movie", "TVSeries", "VideoGame"},
     * )
     *
     * @var string
     */
    public $type;

    /**
     * @OA\Property(
     *     default="",
     *     example="The Matrix",
     *     description="Title",
     *     title="Title",
     *     type="string",
     * )
     *
     * @var string
     */
    public $title;

    /**
     * @OA\Property(
     *     default=0,
     *     example=1999,
     *     format="in32",
     *     description="Year",
     *     title="Year",
     * )
     *
     * @var int
     */
    public $year;

    /**
     * @OA\Property(
     *     default="",
     *     example="1999-03-31",
     *     description="Release date",
     *     title="Release date",
     *     type="string",
     * )
     *
     * @var String
     */
    public $release;

    /**
     * @OA\Property(
     *     default="",
     *     example="tt0133093",
     *     type="string",
     *     description="IMDB ID",
     *     title="IMDB ID",
     * )
     *
     * @var String
     */
    public $imdb_id;

    /**
     * @OA\Property(
     *     type="object",
     *     properties={
     *          @OA\Property(property="cast", type="array", @OA\Items(ref="#/components/schemas/CastName")), 
     *          @OA\Property(property="demographic", type="array", @OA\Items(ref="#/components/schemas/Demographic")),
     *     },
     *     description="Cast stars list",
     *     title="Cast stars",
     * )     
     * 
     * @var String
     */
    public $cast_stars;

    /**
     * @OA\Property(
     *     type="object",
     *     properties={
     *          @OA\Property(property="cast", type="array", @OA\Items(ref="#/components/schemas/CastName")), 
     *          @OA\Property(property="demographic", type="array", @OA\Items(ref="#/components/schemas/Demographic")),
     *     },
     *     description="Cast supporting list",
     *     title="Cast stupporting",
     * )     
     * 
     * @var String
     */
    public $cast_stupporting;

    /**
     * @OA\Property(
     *     type="object",
     *     properties={
     *          @OA\Property(property="cast", type="array", @OA\Items(ref="#/components/schemas/CastName")), 
     *          @OA\Property(property="gender", type="array", @OA\Items(ref="#/components/schemas/Gender")),
     *          @OA\Property(property="demographic", type="array", @OA\Items(ref="#/components/schemas/Demographic")),
     *     },
     *     description="Cast all list",
     *     title="Cast",
     * )     
     * 
     * @var String
     */
    public $cast_all;

    public function __construct($arr = array(), $sf='') {

        $this->setIntVal($arr, 'id');
        $this->setVal($arr, 'type');
        $this->setVal($arr, 'title');
        $this->setIntVal($arr, 'year');
        $this->setVal($arr, 'release');

	    $this->setVal($arr, 'runtime');

	    $this->setVal($arr, 'boxusa');
	    $this->setVal($arr, 'boxworld');
	    $this->setVal($arr, 'budget');
	    $this->setVal($arr, 'boxprofit');
	    $this->setVal($arr, 'provider');
	    $this->setVal($arr, 'data');

	    $this->setVal($arr, 'rimdb');
	    $this->setVal($arr, 'rrt');
	    $this->setVal($arr, 'rrta');


	    $this->setVal($arr, 'genre');
	    $this->setVal($arr, 'country');



        $this->get_imdb_id($arr);

        // Actor objects
        // get actor data
        $actor_names = array();
        if ($sf) {
            $actor_names = $sf->cs->get_actor_names(explode(',', $arr['actor_all']));
        }
        // 'actor_all','actor_star','actor_main',
        try {
            $this->cast_stars = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_star']), $actor_names),
                'gender' => $this->get_gender('s', $arr),
                'demographic' => $this->get_demographic('s', $arr),
            );
            $this->cast_stupporting = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_main']), $actor_names),
                'gender' => $this->get_gender('s', $arr),
                'demographic' => $this->get_demographic('m', $arr),
            );
            $this->cast_all = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_all']), $actor_names),
                'gender' => $this->get_gender('s', $arr),
                'demographic' => $this->get_demographic('a', $arr),
            );
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
        }
    }
	public function format_movie_runtime($data, $format = null) {
		if (is_numeric($data)) {
			$format = 'G \h i \m\i\n';
			$output = date($format, mktime(0, 0, $data));

			return $output;
		}
	}



    public function toArray() {
        $ret = array(
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'year' => $this->year,

            'genre' => $this->movie_genre($this->genre,'data_movie_genre'),
            'country' => $this->movie_genre($this->country,'data_movie_country'),
            'language' => $this->data->language,
			'production'=> $this->data->production,
			'description'=> $this->data->description,
            'runtime' => $this->format_movie_runtime($this->runtime),
			'poster'=>$this->to_poster($this->id),
			'rating'=>[
            'imdb'=> $this->rimdb/10,
            'rt'=> $this->rrt,
            'rt_audience'=> $this->rrta,
			],

			'justwatch_provider'=>$this->to_array($this->provider),
			'finances'=>['domestic_box' => $this->boxusa,
			             'world_box' => $this->boxworld,
			             'budget' => $this->budget,
			             'profit' => $this->boxprofit,],




            'release' => $this->release,
            'imdb_id' => $this->imdb_id,
            'cast_stars' => $this->cast_stars,
            'cast_stupporting' => $this->cast_stupporting,
            'cast_all' => $this->cast_all,
        );

        return $ret;
    }

	public function	to_array($data)
{
	if ($data)
	{
		if (strstr($data,','))
		{
			$data_array = explode(',',$data);
		}
		else
		{
			$data_array[]=$data;
		}

		if ($data_array)
		{
			return $data_array;
		}
	}

}

	public function movie_genre($genre,$db )
	{
		if (!$genre)return;


		if (strstr($genre,','))
		{
			$genre_array = explode(',',$genre);
		}
		else
		{
			$genre_array[]=$genre;
		}
		foreach ($genre_array as $gid)
		{
			$gid = trim($gid);

			$where.=" OR id='".$gid."' ";
		}
		if ($where)
		{
			$where = substr($where,3);
		}

		$sql = "SELECT * FROM `".$db."` WHERE (".$where.") ";

		$db = new Controller();
		$data = $db->db_results($sql);
		foreach ($data as $r)
		{
			$res_array[]=['id'=>$r->id,'name'=>$r->name];
		}
		if ($res_array)
		{
			return $res_array;
		}

	}


	public static function to_poster($id) {

		return 'https://img.filmdemographics.com/poster/'.$id.'.jpg';

	}


    public static function getSearchFields() {
        $fields=array(
            'actor_all','actor_star','actor_main',            
            'paaw','paaea','paah','paab','paai','paam','paamix','paajw','pama','pafa',
            'psaw','psaea','psah','psab','psai','psam','psamix','psajw','psma','psfa',
            'pmaw','pmaea','pmah','pmab','pmai','pmam','pmamix','pmajw','pmma','pmfa',            
        );
        return $fields;
    }
    
    public static function getGenderNames() {
        $names = array(
            'm' => array('key' => 2, 'title' => 'Male'),
            'f' => array('key' => 1, 'title' => 'Female'),
        );
        return $names;
    }

    public static function getRaceNames() {
        $names = array(
            'a' => array('key' => 0, 'title' => 'All'),
            'w' => array('key' => 1, 'title' => 'White'),
            'ea' => array('key' => 2, 'title' => 'Asian'),
            'h' => array('key' => 3, 'title' => 'Latino'),
            'b' => array('key' => 4, 'title' => 'Black'),
            'i' => array('key' => 5, 'title' => 'Indian'),
            'm' => array('key' => 6, 'title' => 'Arab'),
            'mix' => array('key' => 7, 'title' => 'Mixed / Other'),
            'jw' => array('key' => 8, 'title' => 'Jewish'),
        );
        return $names;
    }

    private function get_gender($type = 'a', $arr = array()) {
        $gender_names = $this->getGenderNames();

        $ret = array();
        foreach ($gender_names as $key => $value) {
            $field = "p{$type}{$key}a";
            $title = $value['title'];
            $percent = $arr[$field];
            $data = array('gender' => $title, 'percent' => $percent);
            $gender = new Gender($data);
            $ret[] = $gender;
        }
        return $ret;
    }

    private function get_demographic($type = 'a', $arr = array()) {
        $race_names = $this->getRaceNames();
        $ret = array();
        foreach ($race_names as $key => $value) {
            if ($key == 'a') {
                continue;
            }
            $field = "p{$type}a{$key}";
            $race = $value['title'];
            $percent = $arr[$field];
            $data = array('race' => $race, 'race_id' => $value['key'], 'percent' => $percent);
            $demographic = new Demographic($data);
            $ret[] = $demographic;
        }
        return $ret;
    }

    private function get_cast_names($ids = array(), $names = array()) {
        $ret = array();
        foreach ($ids as $id) {
            if (isset($names[$id])) {
                $arr = array('id' => $id, 'name' => $names[$id]);
                $castName = new CastName($arr);
                $ret[] = $castName;
            }
        }
        return $ret;
    }

    private function get_imdb_id($arr) {
        $imdb = isset($arr['movie_id']) ? $arr['movie_id'] : '';
        if ($imdb) {
            $this->imdb_id = 'tt' . sprintf('%07d', $imdb);
        }
    }
}
