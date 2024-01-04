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
class StrUriController extends Controller {

    private $preview_mode = false;
    private $preview_limit = 10;

    public function __construct($preview_mode = false) {
        $this->preview_mode = $preview_mode;
    }

    public function runPath($command = '', $query_args = []) {
        $sfunction = 'getMediaBystrURI';
        $seach_arr = array(
            'chart' => 'getChartDataBystrURI',
            'facets' => 'getFacetsBystrURI',
        );
        if (isset($seach_arr[$command])) {
            // Check paths
            $sfunction = $seach_arr[$command];
        }
        $this->$sfunction($query_args);
    }

    /**
     * @OA\Get(
     *     path="/string_uri",
     *     tags={"string_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getMediaBystrURI",
     *     @OA\Parameter(
     *         name="strURI",
     *         in="query",
     *         description="Type to find media by string request",
     *         example="/search/show_ratings_rrwt/type_movies/rrwt_70-100",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     @OA\JsonContent(
     *         type="object",
     *     ),),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getMediaBystrURI($query_args = []) {

        $strURI = isset($query_args['strURI']) ? htmlspecialchars($query_args['strURI']) : '';

        $uri_arr = explode('/', $strURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'search' || $first_str == 'analytics') {
            // Search URI
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $strURI;
            if ($first_str == 'search') {
                $sf = new \SearchFacets();
                $sf->init_search_filters();
                $result = $sf->find_results(0, array(), false, true);
            } else {
                // Analytics URI
                $sf = $this->getAnSearchFront();
                $sf->init_search_filters();
                $result = $sf->find_results(0, array(), false, true);
            }
            if ($this->preview_mode) {
                $result = $this->get_preview_result($result, $this->preview_limit);
            }
            // Deinit url
            $_SERVER['REQUEST_URI'] = $last_req;
        } else {
            return $this->responce(405);
        }

        if ($result) {
            return $this->responce(200, $result);
        } else {
            return $this->responce(404);
        }
    }

    /**
     * @OA\Get(
     *     path="/string_uri/chart",
     *     tags={"string_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getChartDataBystrURI",
     *     @OA\Parameter(
     *         name="strURI",
     *         in="query",
     *         description="Type to find media by string request",
     *         example="/analytics/tab_ethnicity/release_2020-2030/type_movies/vis_scatter/xaxis_rimdb/yaxis_release/showcast_1",
     *         required=true,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     @OA\JsonContent(
     *         type="object",
     *     ),),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getChartDataBystrURI($query_args = []) {

        $strURI = isset($query_args['strURI']) ? htmlspecialchars($query_args['strURI']) : '';

        $uri_arr = explode('/', $strURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'analytics') {
            // Analytics URI only
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $strURI;

            $sf = $this->getAnSearchFront();
            $sf->init_search_filters();

            $tab_key = $sf->get_tab_key();
                                    
            $find_results = $sf->find_results(0, array(), false, true, -1, -1, false, true);
            $preview_limit=0;
            if ($this->preview_mode){
                $preview_limit=$this->preview_limit;
            }
            
            $result = $sf->get_page_facet($find_results, $tab_key, $preview_limit);

            /*if ($this->preview_mode) {                
                $result = $this->get_preview_result($result, $this->preview_limit);                
            }*/
            // Deinit url
            $_SERVER['REQUEST_URI'] = $last_req;
        } else {
            return $this->responce(405);
        }

        if ($result) {
            return $this->responce(200, $result);
        } else {
            return $this->responce(404);
        }
    }

    /**
     * @OA\Get(
     *     path="/string_uri/facets",
     *     tags={"string_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getFacetsBystrURI",
     *     @OA\Parameter(
     *         name="strURI",
     *         in="query",
     *         description="Type to find media by string request",
     *         example="/search/show_ratings_rrwt/type_movies/rrwt_70-100",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     @OA\JsonContent(
     *         type="object",
     *     ),),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Invalid input"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getFacetsBystrURI($query_args = []) {

        $strURI = isset($query_args['strURI']) ? htmlspecialchars($query_args['strURI']) : '';

        $uri_arr = explode('/', $strURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'search' || $first_str == 'analytics') {
            // Search URI
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $strURI;
            if ($first_str == 'search') {
                $sf = new \SearchFacets();
                $sf->init_search_filters();
                $result = $sf->find_results(0, array(), true, true, -1, -1, false, false);
            } else {
                // Analytics URI
                $sf = $this->getAnSearchFront();
                $sf->init_search_filters();
                $find_results = $sf->find_results(0, array(), true, true, -1, -1, false, false);
                $tab_key = $sf->get_tab_key();
                $result = $sf->get_page_facet($find_results, $tab_key);
            }
            // Deinit url
            $_SERVER['REQUEST_URI'] = $last_req;
        } else {
            return $this->responce(405);
        }

        if ($result) {
            return $this->responce(200, $result);
        } else {
            return $this->responce(404);
        }
    }
}
