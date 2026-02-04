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
 *     title="Rating model",
 *     description="Rating model",
 * )
 */
class Rating extends Model {

    /**
     * @OA\Property(
     *     default="",
     *     example="rimdb",
     *     description="Rating ID",
     *     title="Rating ID",
     *     type="string",
     * )
     *
     * @var string
     */
    public $id;
    
    /**
     * @OA\Property(
     *     default="",
     *     example="IMDb",
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
     *     example=9.8,
     *     format="float",
     *     title="Value",
     * )
     *
     * @var float
     */
    public $value;

    public function __construct($arr = array()) {
        $this->setVal($arr, 'id');
        $this->setVal($arr, 'title');
        $this->setVal($arr, 'value');
        
    }

    public function toArray() {
        return array(
            'id' => $this->id,          
            'title' => $this->title,
            'value' => $this->value,
        );
    }
    
}
