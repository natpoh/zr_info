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
 *     title="CastName model",
 *     description="CastName model",
 * )
 */
class CastName extends Model {

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
    
    public function __construct($arr = array()) {

        $this->setIntVal($arr, 'id');
        $this->setVal($arr, 'name');       
   
    }

    public function toArray() {
        return array(
            'id' => $this->id,
            'name' => $this->name,    
        );
    }
}
