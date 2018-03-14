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
use League\Csv\Reader;
use NVL\SleepCloud\Event;
use Slim\Http\{
    Request, Response
};

class ExtDateTime extends DateTime implements \JsonSerializable
{
    public static function createFromFormat($format, $time, DateTimeZone $timezone = null)
    {
        $f = parent::createFromFormat($format, $time, $timezone);
        return new ExtDateTime($f->format("c"));
    }

    public function jsonSerialize()
    {
        return $this->format("c");
    }
}

class APIController extends Controller
{
    private $cache = DIR . '.cache/.sleep/data.csv';

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
                $result[$k][] = ExtDateTime::createFromFormat( 'd. m. Y G:i', $values[$i] ,$tz);
            }
            elseif ($k==='Event')
            {
                // value is an event, KEY-TIMESTAMP
                list( $event, $date) = explode( '-', $values[$i], 2);
                $str = (int)($date/1000);

                try {
                    $result['Events'][] = array(
                        'id' => (string)new Event($event),
                        'date' => (new ExtDateTime("@$str"))->setTimezone($tz)
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
                $result['date'][] = (new ExtDateTime("@$str"))->setTimezone($tz);
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
        /** @var Reader $csv */
        $csv = Reader::createFromPath($this->cache, 'r');

        /** @var array $records */
        $records = $csv->getRecords();

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

        return $response->withJson(['data' => $results], 200);
    }

}