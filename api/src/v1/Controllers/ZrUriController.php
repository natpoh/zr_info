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
class ZrUriController {

    public function runPath($command='', $query_args=[]) {
        $sfunction = 'getMediaByZrURI';
        $seach_arr = array(
            'chart' => 'getChartDataByZrURI',
            'facets' => 'getFacetsByZrURI',
        );
        if (isset($seach_arr[$command])) {
            // Check paths
            $sfunction = $seach_arr[$command];
        }
        $this->$sfunction($query_args);
    }

    /**
     * @OA\Get(
     *     path="/zr_uri",
     *     tags={"zr_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getMediaByZrURI",
     *     @OA\Parameter(
     *         name="zrURI",
     *         in="query",
     *         description="Type to find media by Zr URI",
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
     *         @OA\Property(property="currentPage", type="integer", example=1),
     *         @OA\Property(property="totalPages", type="integer", example=5),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Media")),     
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
    public function getMediaByZrURI($query_args = []) {

        $zrURI = isset($query_args['zrURI']) ? htmlspecialchars($query_args['zrURI']) : '';

        $uri_arr = explode('/', $zrURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'search' || $first_str == 'analytics') {
            // Search URI
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $zrURI;
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
     *     path="/zr_uri/chart",
     *     tags={"zr_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getChartDataByZrURI",
     *     @OA\Parameter(
     *         name="zrURI",
     *         in="query",
     *         description="Type to find media by Zr URI",
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
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Media")),     
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
    public function getChartDataByZrURI($query_args = []) {

        $zrURI = isset($query_args['zrURI']) ? htmlspecialchars($query_args['zrURI']) : '';

        $uri_arr = explode('/', $zrURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'analytics') {
            // Analytics URI only
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $zrURI;

            $sf = $this->getAnSearchFront();
            $sf->init_search_filters();

            $result = $sf->find_results(0, array(), false, true, -1, -1, false, true);

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
     *     path="/zr_uri/facets",
     *     tags={"zr_uri"},
     *     summary="Returns media objects",
     *     description="Returns a map of media objects",
     *     operationId="getFacetsByZrURI",
     *     @OA\Parameter(
     *         name="zrURI",
     *         in="query",
     *         description="Type to find media by Zr URI",
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
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Media")),     
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
    public function getFacetsByZrURI($query_args = []) {

        $zrURI = isset($query_args['zrURI']) ? htmlspecialchars($query_args['zrURI']) : '';

        $uri_arr = explode('/', $zrURI);
        $first_str = isset($uri_arr[1]) ? $uri_arr[1] : '';
        $result = array();

        if ($first_str == 'search' || $first_str == 'analytics') {
            // Search URI
            // Init url
            $last_req = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $zrURI;
            if ($first_str == 'search') {
                $sf = new \SearchFacets();
                $sf->init_search_filters();
                $result = $sf->find_results(0, array(), true, true, -1, -1, false, false);
            } else {
                // Analytics URI
                $sf = $this->getAnSearchFront();
                $sf->init_search_filters();
                $result = $sf->find_results(0, array(), true, true, -1, -1, false, false);
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

    private function responce($code = 200, $data = array()) {
        http_response_code($code);
        header('Content-Type: application/json');
        print json_encode($data);
    }

    private function getAnSearchFront() {
        if (!class_exists('AnalyticsFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
        }
        $sf = new \AnalyticsFront();
        return $sf;
    }
}
