<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 13/03/2018
 * Time: 20:25
 */

namespace NVL\Controllers;


use Crell\ApiProblem\ApiProblem;
use NVL\Services\DataWrangler\Diet;
use NVL\Services\DataWrangler\Health;
use NVL\Services\DataWrangler\Mood;
use Slim\Http\Request;
use Slim\Http\Response;

use Swagger\Annotations as OAS;

/**
 * Class APIController
 * @package NVL\Controllers
 *
 * @OAS\Tag(
 *     name="SleepCloud",
 *     description="Sleep cycle tracker for Android",
 *     @OAS\ExternalDocumentation(
 *         description="Read more",
 *         url="https://sleep.urbandroid.org/"
 *     )
 * )
 *
 * @OAS\Tag(
 *     name="iMood",
 *     description="Mood tracker for Android",
 *     @OAS\ExternalDocumentation(
 *         description="Read more",
 *         url="https://www.imoodjournal.com/"
 *     )
 * )
 *
 */
class APIController extends Controller
{
    /**
     * Route for the OpenAPI specification
     * @param Request  $request
     * @param Response $response
     * @return Response
     */
    public function getOpenAPI(Request $request, Response $response)
    {
        $json = json_decode(@file_get_contents(DIR . 'openapi.json'), true);
        if (!$json) {
            // @todo[vanch3d] Add proper error message (see API Problem Details & Crell/ApiProblem)
            return $response->withJson([], 404);
        }
        return $response->withJson($json, 200)
            ->withHeader('Access-Control-Allow-Origin', '*');
    }

    /**
     * @OAS\Get(
     *     path="/records/sleep",
     *     summary="Get the sleep data from the sleepcloud repository",
     *     tags={"SleepCloud"},
     *     description="",
     *     operationId="getSleepData",
     *     @OAS\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OAS\MediaType(
     *              mediaType="application/json",
     *              @OAS\Schema(
     *                  @OAS\Property(
     *                      property="data",
     *                      type="array",
     *                      @OAS\Items(
     *                          ref="#/components/schemas/Sleep"
     *                      ),
     *                  ),
     *                  @OAS\Property(
     *                      property="metadata",
     *                      type="object"
     *                  ),
     *                  @OAS\Property(property="errors",type="object"),
     *              ),
     *          ),
     *     )
     * )
     *
     * Route for the raw sleep data
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getSleepData(Request $request, Response $response)
    {
        /** @var Mood $db */
        $mood = $this->getContainer()->get("sleep");
        $json = $mood->getData();
        return $response->withJson($json, 200);
    }

    /**
     * Route for the raw mood datat
     * @OAS\Get(
     *     path="/records/mood",
     *     summary="Get the mood data from the iMood Journal repository",
     *     tags={"iMood"},
     *     description="",
     *     operationId="getMoodData",
     *     @OAS\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OAS\MediaType(
     *              mediaType="application/json",
     *              @OAS\Schema(
     *                  @OAS\Property(
     *                      property="data",
     *                      type="array",
     *                      @OAS\Items(
     *                          ref="#/components/schemas/Mood"
     *                      ),
     *                  ),
     *                  @OAS\Property(
     *                      property="metadata",
     *                      type="object",
     *                      @OAS\Property(
     *                          property="hash",
     *                          type="string"
     *                      ),
     *                      @OAS\Property(
     *                          property="tags",
     *                          type="array",
     *                          @OAS\Items(
     *                              ref="#/components/schemas/MoodTag"
     *                          )
     *                      )
     *                  ),
     *                  @OAS\Property(property="errors",type="object"),
     *              ),
     *          ),
     *     )
     * )
     *
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getMoodData(Request $request, Response $response)
    {
        /** @var Mood $db */
        $mood = $this->getContainer()->get("mood");
        $json = $mood->getData();
        return $response->withJson($json, 200);

    }


    /**
     * Route for the mood description tags
     * @OAS\Get(
     *     path="/records/mood/tags",
     *     summary="Get the description tags from the mood records",
     *     tags={"iMood"},
     *     description="",
     *     operationId="getMoodTags",
     *     @OAS\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OAS\MediaType(
     *              mediaType="application/json",
     *              @OAS\Schema(
     *                  @OAS\Property(
     *                      property="data",
     *                      type="array",
     *                      @OAS\Items(
     *                          ref="#/components/schemas/MoodTag"
     *                      ),
     *                  ),
     *                  @OAS\Property(
     *                      property="metadata",
     *                      type="object",
     *                      @OAS\Property(
     *                          property="hash",
     *                          type="string"
     *                      ),
     *                  ),
     *                  @OAS\Property(property="errors",type="object"),
     *              ),
     *          ),
     *     )
     * )
     *
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getMoodTags(Request $request, Response $response)
    {
        /** @var Mood $db */
        $mood = $this->getContainer()->get("mood");
        $json = $mood->getData();
        return $response->withJson([
            'data' => $json['metadata']['tags']
        ], 200);
    }

    /**
     * @OAS\Get(
     *     path="/records/diet",
     *     summary="Get the diet from the mood records",
     *     tags={"iMood"},
     *     description="",
     *     operationId="getDietData",
     *     @OAS\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OAS\MediaType(
     *              mediaType="application/json",
     *              @OAS\Schema(
     *                  @OAS\Property(
     *                      property="data",
     *                      type="array",
     *                      @OAS\Items(
     *                          ref="#/components/schemas/Diet"
     *                      ),
     *                  ),
     *                  @OAS\Property(
     *                      property="metadata",
     *                      type="object",
     *                      @OAS\Property(
     *                          property="hash",
     *                          type="string"
     *                      ),
     *                  ),
     *                  @OAS\Property(property="errors",type="object"),
     *              ),
     *          ),
     *     )
     * )
     *
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getDietData(Request $request, Response $response)
    {
        //$json = $this->parseDietData();
        $diet = $this->getContainer()->get("diet");
        $json = $diet->getData();

        return $response->withJson(array(
            'data' => $json,
        ), 200);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getNutrientData(Request $request, Response $response)
    {
        /** @var Diet $diet */
        $diet = $this->getContainer()->get("diet");
        $json = $diet->getExtendedData();

        return $response->withJson($json, 200);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getActivityData(Request $request, Response $response)
    {
        /** @var Health $health */
        $health = $this->getContainer()->get("activity");
        $json = $health->getActivity();

        return $response->withJson($json, 200);
    }


    /**
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getStepsData(Request $request, Response $response)
    {
        /** @var Health $health */
        $health = $this->getContainer()->get("activity");
        $json = $health->getSteps();

        return $response->withJson($json, 200);
    }



    public function getOntologyFood(Request $request, Response $response)
    {
        // food taxonomy
        $fileCategories = DIR . "public/assets/food_category.json";

        if ($request->isGet())
        {
            $categories = json_decode(file_get_contents($fileCategories), true) ?? [];
            return $response->withJson([
                'data'=>array_values($categories)
            ], 200);

        }
        else if ($request->isPut())
        {
            $parsedBody = $request->getParsedBody();
            $json = json_encode($parsedBody,JSON_PRETTY_PRINT);
            if (($c = json_last_error()) !== JSON_ERROR_NONE)
            {
                $problem = new ApiProblem("Configuration cannot be saved");
                $problem->setDetail(json_last_error_msg())
                    ->setInstance("about:json_encode");
                $problem['code'] = $c;
                return $response->withJson($problem->asArray(),415)
                    ->withHeader('Content-Type', ApiProblem::CONTENT_TYPE_JSON . ';charset=utf-8');
            }
            $res = file_put_contents($fileCategories, $json);
            return $response->withJson(null,200);
        }

        $notFoundHandler = $this->get('notFoundHandler');
        return $notFoundHandler($request->withAttribute('message', "Something went wrong"), $response);



    }
}