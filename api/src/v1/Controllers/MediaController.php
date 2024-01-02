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
class MediaController extends Controller {

    public $sfunction = 'getMediaById';
    public $seach_arr = array(
        'cast' => 'getMediaCast',
        'rating' => 'getMediaRating',
    );
    private $db;

    public function __construct() {
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'actors_imdb' => 'data_actors_imdb',
            'data_genre' => 'data_movie_genre',
            'meta_genre' => 'meta_movie_genre',
            'data_platform' => 'data_game_platform',
            'meta_platform' => 'meta_game_platform',
            'data_country' => 'data_movie_country',
            'meta_country' => 'meta_movie_country',
            'data_provider' => 'data_movie_provider',
            'meta_actor' => 'meta_movie_actor',
            'meta_director' => 'meta_movie_director',
            'actors_meta'=>'data_actors_meta',
        );
    }

    /**
     * @OA\Get(
     *     path="/media/{mediaId}",
     *     tags={"media"},
     *     description="Returns a media object.",
     *     operationId="getMediaById",
     *     @OA\Parameter(
     *         name="mediaId",
     *         in="path",
     *         description="ID of media that needs to be fetched",
     *         required=true,
     *         example=21055,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Media"),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getMediaById($query_args = []) {
        $mediaId = isset($query_args['media_id']) ? (int) $query_args['media_id'] : 0;
        $cs = new \CriticSearch();
        $fmedia = $cs->get_movie_by_id($mediaId);
        if ($fmedia) {
            $sf = $this->get_sf();
            $media = new \OpenApi\Fd\Models\Media((array) $fmedia, $sf);
            $ret = $media->toArray();
            return $this->responce(200, $ret);
        } else {
            return $this->responce(404);
        }
    }

    /**
     * @OA\Get(
     *     path="/media/{mediaId}/cast",
     *     tags={"media"},
     *     description="Returns list of cast objects.",
     *     operationId="getMediaCast",
     *     @OA\Parameter(
     *         name="mediaId",
     *         in="path",
     *         description="ID of media that needs to be fetched",
     *         required=true,
     *         example=21055,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Cast")         
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getMediaCast($query_args = []) {
        $mediaId = isset($query_args['media_id']) ? (int) $query_args['media_id'] : 0;
        $cs = new \CriticSearch();
        $fmedia = $cs->get_movie_by_id($mediaId);
        if ($fmedia) {
            $actor_all = $fmedia->actor_all;
            $actor_star_all = explode(',', $fmedia->actor_star);
            $actor_main_all = explode(',', $fmedia->actor_main);
            $actors_list = array('stars' => [], 'supporting' => [], 'all' => []);
            if ($actor_all) {
                // Get actor data by id
                $sql = "SELECT * FROM {$this->db['actors_imdb']} WHERE id in(" . $actor_all . ")";
                $actors = $this->db_results($sql);
                // Get verdicts
                $verdicts_sql = "SELECT actor_id, gender, n_verdict_weight FROM {$this->db['actors_meta']} WHERE actor_id in(" . $actor_all . ")";
                $verdicts = $this->db_results($verdicts_sql);
                $verdicts_arr = array();
                if ($verdicts){
                    foreach ($verdicts as $verdict) {
                        $verdicts_arr[$verdict->actor_id]=$verdict;
                    }
                }
       
                if ($actors) {
                    $race_titles = array();
                    $race_names = \OpenApi\Fd\Models\Media::getRaceNames();
                    foreach ($race_names as $race_data) {
                        $race_titles[$race_data['key']]=$race_data['title'];
                    }
                    $gender_titles=array();
                    $gender_names = \OpenApi\Fd\Models\Media::getGenderNames();
                    foreach ($gender_names as $gender_data) {
                        $gender_titles[$gender_data['key']]=$gender_data['title'];
                    }
                    
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
                    foreach ($actors as $actor) {
                        $actor->type = 'all';
                        $actor->race="";
                        $actor->gender="";
                        if (in_array($actor->id, $actor_main_all)) {
                            $actor->type = 'stars';
                        } else if (in_array($actor->id, $actor_star_all)) {
                            $actor->type = 'supporting';
                        }
                        // Check verdict
                        if (isset($verdicts_arr[$actor->id])){
                            $race = $verdicts_arr[$actor->id]->n_verdict_weight;
                            if ($race){                                
                                $actor->race=isset($race_titles[$race])?$race_titles[$race]:'';
                                $actor->race_id=$race;
                            }
                            $gender = $verdicts_arr[$actor->id]->gender;
                            if ($gender){
                                $actor->gender=isset($gender_titles[$gender])?$gender_titles[$gender]:'';
                            }                            
                        }
                        $cast = new \OpenApi\Fd\Models\Cast((array) $actor);
                        $actors_list[$actor->type][] = $cast;
                    }
                }
            }
            $ret_stars = array_merge($actors_list['stars'], $actors_list['supporting']);
            $ret = array_merge($ret_stars, $actors_list['all']);

            return $this->responce(200, $ret);
        } else {
            return $this->responce(404);
        }
    }

    /**
     * @OA\Get(
     *     path="/media/{mediaId}/rating",
     *     tags={"media"},
     *     description="Returns list of rating objects.",
     *     operationId="getMediaRating",
     *     @OA\Parameter(
     *         name="mediaId",
     *         in="path",
     *         description="ID of media that needs to be fetched",
     *         required=true,
     *         example=21055,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Rating")         
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     ),
     *     security={
     *         {"api_key": {}}
     *     }
     * )
     */
    public function getMediaRating($query_args = []) {
        $mediaId = isset($query_args['media_id']) ? (int) $query_args['media_id'] : 0;
        $cs = new \CriticSearch();
        $fmedia = $cs->get_movie_by_id($mediaId);
        if ($fmedia) {            
            $ratings_list = $cs->facet_data['ratings']['childs'];            
            $items = array();
            $fmedia_arr = (array) $fmedia;
            foreach ($ratings_list as $rkey => $item) {
                
                if (isset($item['facet']) && $item['facet'] == 'rating') {                    
                    $rating_val = isset($fmedia_arr[$rkey])?$fmedia_arr[$rkey]:0;                    
                   if ($rkey!='aurating' && $rkey!='rrev')
                   {
                    if ($rating_val){
                        $rating_multi = (int) $rating_val;
                        if (isset($item['multipler'])){
                            $rating_multi = round($rating_val/$item['multipler'],2);
                        }
						if ($rkey=='rrwt')
						{
							$item['title'] ='Aggregate Rating';
						}
                        $item_arr = array(
                            'id'=>$rkey,
                            'title'=>$item['title'],
                            'value'=>$rating_multi,
                        );
                        $rating = new \OpenApi\Fd\Models\Rating($item_arr);
                        $items[] = $rating->toArray();
                    }
				   }
                }
            }
            return $this->responce(200, $items);
        } else {
            return $this->responce(404);
        }
    }
}
