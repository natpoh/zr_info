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

    public function __construct($arr = array()) {
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
    }

    public function toArray() {
        return array(
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'year' => $this->year,
            'release' => $this->release,
            'imdb_id' => $this->imdb_id,
        );
    }

    private function get_imdb_id($arr) {
        $imdb = isset($arr['movie_id']) ? $arr['movie_id'] : '';
        if ($imdb) {
            $this->imdb_id = 'tt' . sprintf('%07d', $imdb);
        }
    }
}
