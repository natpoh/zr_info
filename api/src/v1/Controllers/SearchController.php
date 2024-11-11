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

    public function __construct($preview_mode = false) {
        
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
     *         description="Search string (e.g., 'Matrix'). Type to find media by title and release year",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="kmwoke",
     *         in="query",
     *         description="Filter items by specific keywords related to woke themes. Accepts args:'woke,lgbt,lgb,qtia'. Use a comma-separated list for multiple keywords (e.g., 'woke,lgbt').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-kmwoke",
     *         in="query",
     *         description="Exclude items by specific keywords related to woke themes. Accepts args:'woke,lgbt,lgb,qtia'. Use a comma-separated list for multiple keywords (e.g., 'woke,lgbt').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="woke",
     *         in="query",
     *         description="Woke rating filter. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-woke",
     *         in="query",
     *         description="Exclude items with a certain woke rating. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="lgbt",
     *         in="query",
     *         description="LGBT rating filter. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-lgbt",
     *         in="query",
     *         description="Exclude items with a certain LGBT rating. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="lgb",
     *         in="query",
     *         description="LGB rating filter. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-lgb",
     *         in="query",
     *         description="Exclude items with a certain LGB rating. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="qtia",
     *         in="query",
     *         description="QTIA rating filter. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-qtia",
     *         in="query",
     *         description="Exclude items with a certain QTIA rating. Accepts a range in the format 'min-max' (e.g., '1-20'), where both 'min' and 'max' can range from 1 to 20.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Family Friendly Score filter. Rating on a scale from 0.0 to 5.0, multiplied by 10 for API. Enter integers in the range from 0 to 50 in 'min-max' format (e.g. '10-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-rating",
     *         in="query",
     *         description="Exclude items with a certain Family Friendly Score rating. Rating on a scale from 0.0 to 5.0, multiplied by 10 for API. Enter integers in the range from 0 to 50 in 'min-max' format (e.g. '10-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="rmedia",
     *         in="query",
     *         description="MediaVersity filter. Rating on a scale from 0.0 to 5.0, multiplied by 10 for API. Enter integers in the range from 0 to 50 in 'min-max' format (e.g. '10-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-rmedia",
     *         in="query",
     *         description="Exclude items with a certain MediaVersity rating. Rating on a scale from 0.0 to 5.0, multiplied by 10 for API. Enter integers in the range from 0 to 50 in 'min-max' format (e.g. '10-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="mediaversity",
     *         in="query",
     *         description="MediaVersity A-F filter. Accepts args:'a,b,c,d,f'. Use a comma-separated list for multiple keywords (e.g., 'a,b').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-mediaversity",
     *         in="query",
     *         description="Exclude items by specific keywords related to MediaVersity A-F themes. Accepts args:'a,b,c,d,f'. Use a comma-separated list for multiple keywords (e.g., 'a,b').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="rcherry",
     *         in="query",
     *         description="CherryPicks filter. Enter integers in the range from 0 to 100 in 'min-max' format (e.g. '0-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-rcherry",
     *         in="query",
     *         description="Exclude items with a certain CherryPicks rating. Enter integers in the range from 0 to 100 in 'min-max' format (e.g. '0-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="wokeornot",
     *         in="query",
     *         description="WokerNot filter. Enter integers in the range from 0 to 100 in 'min-max' format (e.g. '0-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-wokeornot",
     *         in="query",
     *         description="Exclude items with a certain WokerNot rating. Enter integers in the range from 0 to 100 in 'min-max' format (e.g. '0-40').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="bechdeltest",
     *         in="query",
     *         description="BechdelTest filter. Accepts args:'nowomen,notalk,talk,pass'. Use a comma-separated list for multiple keywords (e.g., 'talk,pass').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-bechdeltest",
     *         in="query",
     *         description="Exclude items by specific keywords related to BechdelTest themes. Accepts args:'nowomen,notalk,talk,pass'. Use a comma-separated list for multiple keywords (e.g., 'talk,pass').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="worthit",
     *         in="query",
     *         description="WorthItOrWoke filter. Accepts args:'worthit,nonwoke,wokeish,woke'. Use a comma-separated list for multiple keywords (e.g., 'wokeish,woke').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-worthit",
     *         in="query",
     *         description="Exclude items by specific keywords related to WorthItOrWoke themes. Accepts args:'worthit,nonwoke,wokeish,woke'. Use a comma-separated list for multiple keywords (e.g., 'wokeish,woke').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="auvote",
     *         in="query",
     *         description="Audience Warnings Suggestion filter. Accepts args:'pay,free,skip'. Use a comma-separated list for multiple keywords (e.g., 'pay,free').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-auvote",
     *         in="query",
     *         description="Exclude items by specific keywords related to Audience Warnings Suggestion themes. Accepts args:'pay,free,skip'. Use a comma-separated list for multiple keywords (e.g., 'pay,free').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="auneo",
     *         in="query",
     *         description="Audience Warnings NEO-MARXISM filter. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-auneo",
     *         in="query",
     *         description="Exclude items with a certain Audience NEO-MARXISM rating. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="aumisandry",
     *         in="query",
     *         description="Audience Warnings FEMINISM filter. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-aumisandry",
     *         in="query",
     *         description="Exclude items with a certain Audience FEMINISM rating. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="auaffirmative",
     *         in="query",
     *         description="Audience Warnings AFFIRMATIVE ACTION filter. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-auaffirmative",
     *         in="query",
     *         description="Exclude items with a certain Audience AFFIRMATIVE ACTION rating. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="aulgbtq",
     *         in="query",
     *         description="Audience Warnings GAY STUFF filter. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-aulgbtq",
     *         in="query",
     *         description="Exclude items with a certain Audience GAY STUFF rating. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="augod",
     *         in="query",
     *         description="Audience Warnings FEDORA TIPPING filter. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
     *         @OA\Schema(
     *             type="string",     
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="minus-augod",
     *         in="query",
     *         description="Exclude items with a certain Audience FEDORA TIPPING rating. Enter integers in the range from 0 to 5 in 'min-max' format (e.g. '0-5').",
     *         required=false,
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

        $credentials = new \OpenApi\Fd\Credentials();
        $validate_key = $credentials->validateApiKey($query_args);
        if ($validate_key == 0) {
            return $this->responce_unauthorized();
        }

        $pp = isset($query_args['pp']) ? (int) $query_args['pp'] : 20;
        $p = isset($query_args['p']) ? (int) $query_args['p'] : 1;
        $sf = $this->get_sf();

        // Init filters
        $sf->api_init_search_get_fiters($query_args);

        $fields = \OpenApi\Fd\Models\Media::getSearchFields();
        $result = $sf->find_results(0, array(), false, true, $pp, -1, true, false, $fields);

        $count = isset($result['movies']['count']) ? (int) $result['movies']['count'] : 0;
        $totalPages = ceil($count / $pp);

        $ret = array(
            "currentPage" => $p,
            "totalPages" => $totalPages,
            "totalCount" => $count,
            "data" => $this->getMediaFromList($result['movies']['list']),
        );
        return $this->responce(200, $ret);
    }

    private function getMediaFromList($data = array()) {
        $ret = array();
        if ($data) {
            $sf = $this->get_sf();
            foreach ($data as $item) {
                print_r($item);
                $media = new \OpenApi\Fd\Models\Media((array) $item, $sf);
                $ret[] = $media->toArray();
            }
        }
        return $ret;
    }
}
