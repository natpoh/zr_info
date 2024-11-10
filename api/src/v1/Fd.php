<?php

/**
 * @license Apache 2.0
 */

namespace OpenApi\Fd;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     description="",
 *     version="1.0.0",
 *     title="FilmDemographics API",
 *     termsOfService="http://swagger.io/terms/",
 *     @OA\Contact(
 *         email="apiteam@swagger.io"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Tag(
 *     name="search",
 *     description="Find media by titles",
 * )
 * @OA\Tag(
 *     name="media",
 *     description="Get media info",
 * )
 * @OA\Tag(
 *     name="string_uri",
 *     description="Get media by string request",
 * )
 * @OA\Server(
 *     description="FilmDemographics API Mocking",
 *     url="https://api.filmdemographics.com/v1"
 * )
 * @OA\Server(
 *     description="Zgreviews API Mocking",
 *     url="https://api.zgreviews.com/v1"
 * )
 * #OA\Server(
 *     description="DEV FilmDemographics API Mocking",
 *     url="http://api.zr.4aoc.ru/v1"
 * )
 */
class Fd
{
}
