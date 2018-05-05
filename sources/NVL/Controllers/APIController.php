<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 13/03/2018
 * Time: 20:25
 */

namespace NVL\Controllers;


use Crell\ApiProblem\ApiProblem;
use DateTimeZone;
use FilesystemIterator;
use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use League\Csv\Reader;
use NVL\Models\SleepCloud\Event;
use NVL\Support\JSONDateTime;
use Slim\Http\Request;
use Slim\Http\Response;
use stdClass;
use Tracy\Debugger;
use ZipArchive;

use Swagger\Annotations as OAS;

/**
 * Class APIController
 * @package NVL\Controllers
 * @todo[vanch3d] Break the controller up, separating data sources in individual Slim containers
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
     * @var string Path to the cached data
     */
    private $cachePath = DIR . '.cache/.sleep/';

    /**
     * @param string $dropboxfilename
     * @param string $ext
     * @return null|string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getFromDropBox(string $dropboxfilename, string $ext)
    {
        $dbapp = new DropboxApp(
            null,
            null,
            getenv("DROPBOX_TOKEN"));

        $dropbox = new Dropbox($dbapp);

        $filename = null;
        try {
            //Get File Metadata
            $fileMetadata = $dropbox->getMetadata($dropboxfilename);
            Debugger::barDump($fileMetadata);

            $hash = $fileMetadata->getData()['content_hash'];
            $filename = $this->cachePath . $hash . $ext;
            $this->getLogger()->notice("check data hash value", array(
                'hash' => $hash));

            // check if hashed cache exists
            if (!file_exists($filename)) {
                $this->getLogger()->notice("file does not exist. clean");

                // clear cache folder
                $di = new RecursiveDirectoryIterator($this->cachePath, FilesystemIterator::SKIP_DOTS);
                $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($ri as $file) {
                    // $file->isDir() ?  rmdir($file) : unlink($file);
                }
                $dropbox->download($dropboxfilename, $filename);
                $this->getLogger()->notice("file downloaded from dropbox");
            }


        } catch (DropboxClientException $e) {
            // @todo[vanch3d] check for exception handling
        }
        $this->getLogger()->notice("file downloaded from dropbox", array(
            'file' => $filename));

        return $filename;
    }

    /**
     * @param $keys
     * @param $values
     * @return array
     */
    private function data_processing($keys, $values)
    {
        $result = array();

        $idx = array_search("Tz", $keys);
        $tz = new DateTimeZone($values[$idx]);

        foreach ($keys as $i => $k) {
            if ($k === 'From' || $k === 'To' || $k === 'Sched') {
                // value is a date
                $result[$k][] = JSONDateTime::createFromFormat('d. m. Y G:i', $values[$i], $tz);
            } elseif ($k === 'Event') {
                // value is an event, KEY-TIMESTAMP
                list($event, $date) = explode('-', $values[$i], 2);
                $str = (int)($date / 1000);

                try {
                    $result['Events'][] = array(
                        'id' => (string)new Event($event),
                        'date' => (new JSONDateTime("@$str"))->setTimezone($tz)
                    );
                } catch (\Exception $e) {
                    // @todo[vanch3d] Deal with unexpected value
                }
            } elseif (preg_match("/^(?:2[0-3]|[01][0-9]|[0-9]):[0-5][0-9]$/", $k)) {
                // value is a time (actigraph)
                // @todo[vanch3d] Change id (h:m) to full date/time
                $result['Actigraph'][] = array(
                    'id' => $k,
                    'value' => $values[$i]
                );
            } else {
                $result[$k][] = $values[$i];
            }
            if ($k === 'Id') {
                // extract Date from Id
                $str = (int)($values[$i] / 1000);
                $result['Date'][] = (new JSONDateTime("@$str"))->setTimezone($tz);
            }
        }
        // combine multiple values
        array_walk($result, function (&$v) {
            $v = (count($v) == 1) ? array_pop($v) : $v;
        });
        return $result;


    }

    /**
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
     *
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getSleepData(Request $request, Response $response)
    {
        $this->getLogger()->notice("start loading data");
        $cache = $this->getFromDropBox(getenv("SLEEPCLOUD_DATAFILE"), ".csv");
        $hash = basename($cache, ".csv");
        $this->getLogger()->notice("load cached data");

        /** @var Reader $csv */
        $csv = Reader::createFromPath($cache, 'r');

        /** @var array $records */
        $records = $csv->getRecords();

        $metadata = [
            'hash' => $hash,
            'dates' => [],
            'count' => [
                'records' => 0,
                'levels' => 0,
                'events' => 0,
            ]
        ];

        $header = null;
        $values = null;
        $noise = null;

        $results = array();
        foreach ($records as $offset => $record) {
            if ($record[0] === "Id") {
                // first line of data (headers)
                if ($header != null && $values != null) {
                    // next series of data; format and save records
                    $data = $this->data_processing($header, $values);
                    $data['Levels'] = $noise ? $noise : [];
                    $results[] = $data;

                    $metadata['dates'][] = $data['Date'];
                    $metadata['count']['levels'] += count($data['Levels']);
                    $metadata['count']['events'] += count($data['Events']);

                    $header = null;
                    $values = null;
                    $noise = null;
                }
                $header = $record;
            } else if ($header !== null && $values === null) {
                // second line of data (values)
                $values = $record;
            } else {
                // optionally third line of data (noise levels)
                $noise = $record;
            }

        }

        $metadata['count']['records'] += count($results);

        return $response->withJson([
            'data' => $results,
            'metadata' => $metadata
        ], 200);
    }

    private function parseMoodData()
    {
        $cache = $this->getFromDropBox(getenv("IMOOD_DATAFILE"), ".zip");
        $hash = basename($cache, ".zip");

        $zip = new ZipArchive;
        $res = $zip->open($cache);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $metadata['list'][] = $zip->statIndex($i);
        }

        $metadata = [];
        $data = [];
        $tags = [];

        $im_string = $zip->getFromName("/backup.json");
        $data = json_decode($im_string, true);

        foreach ($data as &$records) {
            $alltags = [];
            foreach ($records['moodTags'] as $moodtag) {
                $name = $moodtag['tagName'];
                if (!isset($tags[$name])) {
                    $tags[$name] = $moodtag;
                }
                $alltags[] = $name;
            }

            // reformat date
            $date = $records['date'];
            $date = new JSONDateTime($date, new DateTimeZone("Europe/London"));
            $records['date'] = $date;

            unset($records['moodTags']);
            $records['moodTags'] = $alltags;

        }

        $metadata['hash'] = $hash;
        $metadata['tags'] = $tags;
        return [
            'data' => $data,
            'metadata' => $metadata
        ];
    }

    /**
     *
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
        $json = $this->parseMoodData();
        return $response->withJson($json, 200);

    }


    /**
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
     */
    public function getMoodTags(Request $request, Response $response)
    {
        $json = $this->parseMoodData();
        return $response->withJson([
            'data' => $json['metadata']['tags']
        ], 200);
    }

    private function parseDietData()
    {
        $keywords = array("#breakfast", "#lunch", "#dinner", "#snacks", "#sandwich");

        $json = $this->parseMoodData();
        $data = [];

        foreach ($json['data'] as $record) {
            $comment = $record['moodDescription']['comment'] ?? "";
            $arr = explode("\n", $comment);

            foreach ($arr as $item) {
                $match = array_intersect(explode(' ', $item), $keywords);
                if (count($match) > 0) {
                    $newRec = array(
                        'type' => $match[0],
                        'date' => $record['date'],
                        'items' => array()
                    );
                    $regex = '~(#\w+)~';
                    if (preg_match_all($regex, $item, $matches, PREG_PATTERN_ORDER)) {
                        foreach ($matches[1] as $word) {
                            if ($word !== $match[0])
                                $newRec['items'][] = ltrim($word, "#");
                        }
                    }
                    $data[] = $newRec;
                }
            }
        }
        return $data;
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
     */
    public function getDietData(Request $request, Response $response)
    {
        $json = $this->parseDietData();
        return $response->withJson(array(
            'data' => $json,
        ), 200);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @return mixed
     */
    public function getNutrientData(Request $request, Response $response)
    {
        $errors = new stdClass();
        $errors->items = array();
        $errors->nutrients = array();
        $errors->category = array();

        $json = $this->parseDietData();

        foreach ($json as $diet)
            $data = array_merge($data ?? [], $diet['items']);
        $data = array_merge([], array_unique($data));

        // food taxonomy
        $fileCategories = DIR . "public/assets/food_category.json";
        $tempCat = json_decode(@file_get_contents($fileCategories), true) ?? [];

        $categories = [];
        $nutrients = [];
        foreach ($tempCat as $cat)
            $categories[$cat['id']] = $cat;

        $client = new Client();

        foreach ($data as $foodItem) {
            if (isset($categories[$foodItem]) &&
                isset($categories[$foodItem]['group']) &&
                $categories[$foodItem]['group'] !== null) continue;

            $errors->category[] = $foodItem;
            $categories[$foodItem] = array(
                "id" => $foodItem,
                "group" => null
            );

        }
        if (!empty($errors->category))
            file_put_contents($fileCategories, json_encode($categories,JSON_PRETTY_PRINT));

        if (getenv("APP_FOOD_NUTRIENTS") === "true") {

            // food nutrients
            $fileNutrients = $this->cachePath . "nutrients,json";
            $nutrients = json_decode(@file_get_contents($fileNutrients), true) ?? [];


            foreach (array_slice($data, 0, 18) as $foodItem) {
                if (isset($nutrients[$foodItem])) continue;
                $searchItem = $foodItem;
                $searchItem = str_replace("flapjack", "English Flapjack", $searchItem);
                $searchItem = str_replace("icecream", "ice cream", $searchItem);
                $searchItem = str_replace("_", " ", $searchItem);

                $subids = [
                    'app_id' => getenv("EDAMAM_APP_ID"),
                    'app_key' => getenv("EDAMAM_APP_KEY"),
                    'ingr' => "100g " . $searchItem
                ];
                $url = getenv("EDAMAM_URL_FOODPARSER");
                $final = $url . "?" . http_build_query($subids);
                $ret = $client->get($final);

                $jsonBody = json_decode((string)$ret->getBody(), true);
                $newItem = array(
                    'id' => $foodItem,
                    'name' => $searchItem,
                    'food' => $jsonBody['parsed'][0]['food'] ?? [],
                    'measure' => $jsonBody['parsed'][0]['measure'] ?? [],
                    'quantity' => $jsonBody['parsed'][0]['quantity'] ?? [],
                    'nutrients' => []
                );

                if (!isset($jsonBody['parsed'][0])) {
                    $errors->items[] = $foodItem;
                }

                $nutrients[$foodItem] = $newItem;
            }
            file_put_contents($fileNutrients, json_encode($nutrients));

            foreach ($nutrients as $key => &$foodItem) {
                if (isset($foodItem['nutrients']) && !empty($foodItem['nutrients'])) continue;
                if (empty($foodItem['measure']) || empty($foodItem['food'])) {
                    $errors->nutrients[] = $foodItem['id'];
                    continue;
                }

                $toProcess = array(
                    'quantity' => $foodItem['quantity'],
                    'measureURI' => $foodItem['measure']['uri'],
                    'foodURI' => $foodItem['food']['uri'],
                );

                $subids = [
                    'app_id' => getenv("EDAMAM_APP_ID"),
                    'app_key' => getenv("EDAMAM_APP_KEY")
                ];
                $url = getenv("EDAMAM_URL_NUTRITION");
                $final = $url . "?" . http_build_query($subids);
                $r = $client->request('POST', $final, ['json' => [
                    'yield' => 1,
                    'ingredients' => [$toProcess]
                ]]);

                $s = (string)$r->getBody();
                $jsonBody = json_decode(utf8_decode($s), true);
                $foodItem['nutrients'] = $jsonBody;
            }
            file_put_contents($fileNutrients, json_encode($nutrients));
        }

        //$tt = array_replace_recursive($nutrients, $categories);
        $gg = array_values(array_unique(array_column($categories, "group")));
        return $response->withJson(array(
            'data' => array_values($categories),
            'metadata' => array(
                'items' => $data,
                'categories' => $gg
            ),
            'error' => $errors
        ), 200);
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


    }
}