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
 *     title="Demographic model",
 *     description="Demographic model",
 * )
 */
class Demographic extends Model {

    /**
     * @OA\Property(
     *     default="",
     *     example="White",     
     *     title="Cast race",
     *     type="string",
     * )
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
     *     default=0,
     *     example=100,
     *     format="int64",
     *     title="Percent",     
     * )
     *
     * @var int
     */
    public $percent;
    
    public function __construct($arr = array()) {
        $this->setVal($arr, 'race');               
        $this->setIntVal($arr, 'race_id'); 
        $this->setIntVal($arr, 'percent');        
    }

    public function toArray() {
        return array(
            'race' => $this->race,
            'race' => $this->race_id,
            'percent' => $this->percent,    
        );
    }
}
