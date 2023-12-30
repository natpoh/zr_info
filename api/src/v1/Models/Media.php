<?php

/**
 * @license Apache 2.0
 */

namespace OpenApi\Fd\Models;

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
     *          @OA\Property(property="demographic", type="array", @OA\Items(ref="#/components/schemas/Demographic")),
     *     },
     *     description="Cast all list",
     *     title="Cast",
     * )     
     * 
     * @var String
     */
    public $cast_all;

    public function __construct($arr = array(), $actor_names = array(), $race_names=array()) {
        /*
         * "id": "33955",
          "rwt_id": "32510",
          "title": "Matrix",
          "release": "1993-03-01",
          "type": "TVSeries",
          "year": "1993",
          "w": "1677",
          "rrt": "0",
          "rrta": "0",
          "rrtg": "0"
         */
        $this->setIntVal($arr, 'id');
        $this->setVal($arr, 'type');
        $this->setVal($arr, 'title');
        $this->setIntVal($arr, 'year');
        $this->setVal($arr, 'release');
        $this->get_imdb_id($arr);

        // Actor objects
        // 'actor_all','actor_star','actor_main',
        try {
            $this->cast_stars = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_star']), $actor_names),
                'demographic' => $this->get_demographic('s', $arr, $race_names),
            );
            $this->cast_stupporting = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_main']), $actor_names),
                'demographic' => $this->get_demographic('m', $arr, $race_names),
            );
            $this->cast_all = array(
                'cast' => $this->get_cast_names(explode(',', $arr['actor_all']), $actor_names),
                'demographic' => $this->get_demographic('a', $arr, $race_names),
            );
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
        }
    }

    public function toArray() {
        $ret = array(
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'year' => $this->year,
            'release' => $this->release,
            'imdb_id' => $this->imdb_id,
            'cast_stars' => $this->cast_stars,
            'cast_stupporting' => $this->cast_stupporting,
            'cast_all' => $this->cast_all,
        );

        return $ret;
    }

    private function get_demographic($type = 'a', $arr = array(), $race_names=array()) {
        /*
            'a' => array('key' => 0, 'title' => 'All'),
            'w' => array('key' => 1, 'title' => 'White'),
            'ea' => array('key' => 2, 'title' => 'Asian'),
            'h' => array('key' => 3, 'title' => 'Latino'),
            'b' => array('key' => 4, 'title' => 'Black'),
            'i' => array('key' => 5, 'title' => 'Indian'),
            'm' => array('key' => 6, 'title' => 'Arab'),
            'mix' => array('key' => 7, 'title' => 'Mixed / Other'),
            'jw' => array('key' => 8, 'title' => 'Jewish'),
         */
        $ret = array();
        foreach ($race_names as $key => $value) {
            if ($key=='a'){
                continue;
            }
            $field = "p{$type}a{$key}";
            $race = $value['title'];
            $percent = $arr[$field];
            $data = array('race'=>$race,'percent'=>$percent);
            $demographic = new Demographic($data);
            $ret[]=$demographic;
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
