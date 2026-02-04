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
 *     title="Gender model",
 *     description="Gender model",
 * )
 */
class Gender extends Model {

    /**
     * @OA\Property(
     *     default="",
     *     example="Male",     
     *     title="Cast gender",
     *     type="string",
     * )
     *
     * @var string
     */
    public $gender;

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
        $this->setVal($arr, 'gender');               
        $this->setIntVal($arr, 'percent');        
    }

    public function toArray() {
        return array(
            'gender' => $this->name,
            'percent' => $this->percent,    
        );
    }
}
