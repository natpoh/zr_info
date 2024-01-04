<?php

/**
 * @license Apache 2.0
 */

namespace OpenApi\Fd\Controllers;

use OpenApi\Annotations as OA;

/**
 * Class Search.
 *
 * @author  Brahmnan <brahmnan@gmail.com>
 */
class SearchController extends Controller {

    public $sfunction = 'searchMedia';
    public $seach_arr = array();
 
    public function __construct($preview_mode=false) {
        
    }
    
    /**
     * @OA\Get(
     *     path="/search",
     *     tags={"search"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="searchMedia",
     *     @OA\Parameter(
     *         name="s",
     *         in="query",
     *         description="Search string. Type to find media by title and release year",
     *         required=false,
     *         example="matrix",
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="p",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         example=1,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="pp",
     *         in="query",
     *         description="Number of results per page: 1-50",
     *         required=false,
     *         example=20,
     *         @OA\Schema(
     *             type="integer",
     *             format="int32"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="currentPage", type="integer", example=1),
     *         @OA\Property(property="totalPages", type="integer", example=5),
     *         @OA\Property(property="totalCount", type="integer", example=100),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Media")),     
     *     ),),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function searchMedia($query_args = []) {
        $pp = isset($query_args['pp']) ? (int) $query_args['pp'] : 20;
        $p = isset($query_args['p']) ? (int) $query_args['p'] : 1;
        $sf = $this->get_sf();

        // Init filters
        $sf->init_search_get_fiters($query_args);
        
        $fields=\OpenApi\Fd\Models\Media::getSearchFields();
        $result = $sf->find_results(0, array(), false, true, $pp,-1,true,false,$fields);

        $ret = array();

        // Movies and tv
        if (isset($result['movies']['list'])) {

            $count = isset($result['movies']['count']) ? (int) $result['movies']['count'] : 0;
            $totalPages = ceil($count / $pp);

            $ret = array(
                "currentPage" => $p,
                "totalPages" => $totalPages,
                "totalCount" => $count,
                "data" => $this->getMediaFromList($result['movies']['list']),
            );
        }


        return $this->responce(200, $ret);
    }

    private function getMediaFromList($data = array()) {
        $ret = array();
        if ($data) {
            $sf = $this->get_sf();
            foreach ($data as $item) {
               $media = new \OpenApi\Fd\Models\Media((array) $item, $sf);
               $ret[]=$media->toArray();
            }
        }
        return $ret;
    }
}
