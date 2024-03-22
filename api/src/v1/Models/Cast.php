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
 *     title="Cast model",
 *     description="Cast model",
 * )
 */
class Cast extends Model {

    /**
     * @OA\Property(
     *     default=0,
     *     example=206,
     *     format="int64",
     *     title="Cast ID",     
     * )
     *
     * @var int
     */
    public $id;

    /**
     * @OA\Property(
     *     default="",
     *     example="Keanu Reeves",     
     *     title="Cast name",
     *     type="string",
     * )
     *
     * @var string
     */
    public $name;

    /**
     * @OA\Property(
     *     default="",
     *     example="Keanu Charles Reeves",     
     *     title="Birth name",
     *     type="string",
     * )
     *
     * @var string
     */
    public $birth_name;

    /**
     * @OA\Property(
     *     default="",
     *     example="Beirut, Lebanon",     
     *     title="Birth place",
     *     type="string",
     * )
     *
     * @var string
     */
    public $birth_place;

    /**
     * @OA\Property(
     *     default="",
     *     example="1964-9-2",     
     *     title="Burn date",
     *     type="string",
     * )
     *
     * @var string
     */
    public $burn_date;

    /**
     * @OA\Property(
     *     default="",
     *     example="stars",     
     *     title="Cast type",
     *     type="string",
     *     enum={"all", "stars", "supporting"},
     * )
     *
     * @var string
     */
    public $type;

    /**
     * @OA\Property(
     *     default="",
     *     example="White",     
     *     title="Cast race",
     *     type="string",
     *     enum={"White", "Asian", "Latino", "Black", "Indian", "Arab", "Mixed / Other", "Jewish"},
     * )
     * 
     *
     * @var string
     */
    public $race;
    
    /**
     * @OA\Property(
     *     default=0,
     *     example=1,
     *     format="int64",
     *     enum={0, 1, 2, 3, 4, 5, 6, 7, 8},
     * )
     * 
     * @var int
     */
    public $race_id;
     
     /**
     * @OA\Property(
     *     default="",
     *     example="Male",     
     *     title="Cast gender",
     *     type="string",
     *     enum={"Male", "Female"},
     * )
     *
     * @var string
     */
    public $gender;
    
    public function __construct($arr = array()) {
        /*
          "id": "206",
          "name": "Keanu Reeves",
          "birth_name": "Keanu Charles Reeves",
          "birth_place": "Beirut, Lebanon",
          "burn_date": "1964-9-2",
          "description": "",
          "image_url": null,
          "image": "Y",
          "lastupdate": "1610561889"
         */

        $this->setIntVal($arr, 'id');
        $this->setVal($arr, 'name');
        $this->setVal($arr, 'birth_name');
        $this->setVal($arr, 'birth_place');
        $this->setVal($arr, 'burn_date');
        $this->setVal($arr, 'type');
        $this->setVal($arr, 'race');
        $this->setIntVal($arr, 'race_id');
        $this->setVal($arr, 'gender');
    }

    public function toArray() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'birth_name' => $this->birth_name,
            'birth_place' => $this->birth_place,
            'burn_date' => $this->burn_date,
            'type' => $this->type,
            'race' => $this->race,
            'race_id' => $this->race_id,
            'gender' => $this->gender,
        );
    }
}
