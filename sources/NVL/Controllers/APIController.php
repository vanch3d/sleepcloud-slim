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
use NVL\SleepCloud\Event;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;

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

class APIController extends Controller
{
    private $cachepath = DIR . '.cache/.sleep/';

    private function getFromDropBox()
    {
        $dbapp = new DropboxApp(
            null,
            null,
            getenv("DROPBOX_TOKEN"));

        $dropbox = new Dropbox($dbapp);

        $filename = null;
        try {
            //Get File Metadata
            $fileMetadata = $dropbox->getMetadata(getenv("SLEEPCLOUD_DATAFILE"));
            Debugger::barDump($fileMetadata);

            $hash = $fileMetadata->getData()['content_hash'];
            $filename = $this->cachepath . $hash . '.csv';

            // check if hashed cache exists
            if (!file_exists($filename)) {
                // clear cache folder
                $di = new RecursiveDirectoryIterator($this->cachepath, FilesystemIterator::SKIP_DOTS);
                $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ( $ri as $file ) {
                    $file->isDir() ?  rmdir($file) : unlink($file);
                }
                $dropbox->download(getenv("SLEEPCLOUD_DATAFILE"), $filename);
            }


        } catch (DropboxClientException $e) {
            // @todo[vanch3d] check for exception handling
        }

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
    public function getSleepData(Request $request, Response $response)
    {
        $cache = $this->getFromDropBox();
        $hash = basename($cache, ".csv");

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

}