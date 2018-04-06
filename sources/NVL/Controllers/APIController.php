<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 13/03/2018
 * Time: 20:25
 */

namespace NVL\Controllers;

use DateTime;
use DateTimeZone;
use FilesystemIterator;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use League\Csv\Reader;
use NVL\Models\SleepCloud\Event;
use RarArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;
use ZipArchive;
use Swagger\Annotations as OAS;

class JSONDateTime extends DateTime implements \JsonSerializable
{
    public static function createFromFormat($format, $time, DateTimeZone $timezone = null)
    {
        $f = parent::createFromFormat($format, $time, $timezone);
        return new JSONDateTime($f->format("c"));
    }

    public function jsonSerialize()
    {
        return $this->format("c");
    }
}

/**
 * Class APIController
 * @package NVL\Controllers
 * @todo[vanch3d] Break the controller up, separating data sources in modules
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
    private $cachepath = DIR . '.cache/.sleep/';

    private function getFromDropBox(string $dropboxfilename,string $ext)
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
            $filename = $this->cachepath . $hash . $ext;
            $this->getLogger()->notice("check data hash value",array(
                'hash' => $hash));

            // check if hashed cache exists
            if (!file_exists($filename)) {
                $this->getLogger()->notice("file does not exist. clean");

                // clear cache folder
                $di = new RecursiveDirectoryIterator($this->cachepath, FilesystemIterator::SKIP_DOTS);
                $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ( $ri as $file ) {
                   // $file->isDir() ?  rmdir($file) : unlink($file);
                }
                $dropbox->download($dropboxfilename, $filename);
                $this->getLogger()->notice("file downloaded from dropbox");
            }


        } catch (DropboxClientException $e) {
            // @todo[vanch3d] check for exception handling
        }
        $this->getLogger()->notice("file downloaded from dropbox",array(
                    'file' => $filename));

        return $filename;
    }

    private function data_processing($keys, $values)
    {
        $result = array();

        $idx = array_search("Tz",$keys);
        $tz = new DateTimeZone($values[$idx]);

        foreach ($keys as $i => $k)
        {
            if ($k==='From' || $k==='To' || $k==='Sched')
            {
                // value is a date
                $result[$k][] = JSONDateTime::createFromFormat( 'd. m. Y G:i', $values[$i] ,$tz);
            }
            elseif ($k==='Event')
            {
                // value is an event, KEY-TIMESTAMP
                list( $event, $date) = explode( '-', $values[$i], 2);
                $str = (int)($date/1000);

                try {
                    $result['Events'][] = array(
                        'id' => (string)new Event($event),
                        'date' => (new JSONDateTime("@$str"))->setTimezone($tz)
                    );
                } catch (\Exception $e) {
                    // @todo[vanch3d] Deal with unexpected value
                }
            }
            elseif (preg_match("/^(?:2[0-3]|[01][0-9]|[0-9]):[0-5][0-9]$/", $k))
            {
                // value is a time (actigraph)
                // @todo[vanch3d] Change id (h:m) to full date/time
                $result['Actigraph'][] = array(
                    'id' => $k,
                    'value' => $values[$i]
                );
            }
            else {
                $result[$k][] = $values[$i];
            }
            if ($k==='Id') {
                // extract Date from Id
                $str = (int)($values[$i]/1000);
                $result['Date'][] = (new JSONDateTime("@$str"))->setTimezone($tz);
            }
        }
        // combine multiple values
        array_walk($result, function(&$v){
            $v = (count($v) == 1)? array_pop($v): $v;
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
        $json = json_decode(@file_get_contents(DIR . 'openapi.json'),true);
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
        $cache = $this->getFromDropBox(getenv("SLEEPCLOUD_DATAFILE"),".csv");
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
                if ($header!=null && $values != null)
                {
                    // next series of data; format and save records
                    $data = $this->data_processing($header,$values);
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
            }
            else if ($header!==null && $values === null) {
                // second line of data (values)
                $values = $record;
            }
            else {
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
     */
    public function getMoodData(Request $request, Response $response)
    {
        $cache = $this->getFromDropBox(getenv("IMOOD_DATAFILE"),".zip");
        $hash = basename($cache, ".zip");

        $zip = new ZipArchive;
        $res = $zip->open($cache);
        for( $i = 0; $i < $zip->numFiles; $i++ ){
            $metadata['list'][] = $zip->statIndex( $i );
        }

        $metadata = [];
        $data = [];
        $tags = [];

        $im_string = $zip->getFromName("/backup.json");
        $data = json_decode($im_string,true);

        foreach ($data as &$records)
        {
            $alltags = [];
            foreach ($records['moodTags'] as $moodtag) {
                $name = $moodtag['tagName'];
                if (!$tags[$name])
                {
                    $tags[$name] = $moodtag;
                }
                $alltags[]= $name;
            }
            unset($records['moodTags']);
            $records['moodTags'] = $alltags;

        }

        $metadata['hash'] = $hash;
        $metadata['tags'] = $tags;

        return $response->withJson([
            'data' => $data,
            'metadata' => $metadata
        ], 200);

    }
}