<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 22:52
 */

namespace NVL\Services\DataWrangler;


use DateTimeZone;
use League\Csv\Reader;
use NVL\Models\SleepCloud\Event;
use NVL\Services\DataProvider\ProviderInterface;
use NVL\Services\DataService;
use NVL\Support\JSONDateTime;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;

class Sleep extends DataService implements WranglerInterface
{
    public $provider;

    public function __construct(array $settings, LoggerInterface $logger, ProviderInterface $provider)
    {
        parent::__construct($settings,$logger);
        $this->provider = $provider;
    }

    protected function validateConfig()
    {
        // TODO: Implement validateConfig() method.
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

    public function getData()
    {
        $wrapper = $this->provider->getHash($this->config['data']['sleep']);
        Debugger::barDump($wrapper);

        if (file_exists($wrapper->cache . ".json"))
        {
            //$this->log("INFO","json already cached",[]);
            $json = json_decode(@file_get_contents($wrapper->cache . ".json"), true);
            return $json;

        }

        //$hash = basename($cache, ".csv");
        //$this->getLogger()->notice("load cached data");

        /** @var Reader $csv */
        $csv = Reader::createFromPath($wrapper->cache, 'r');

        /** @var array $records */
        $records = $csv->getRecords();

        $metadata = [
            'hash' => $wrapper->hash,
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
        $json=[
            'data' => $results,
            'metadata' => $metadata
        ];
        file_put_contents($wrapper->cache . ".json", json_encode($json));

        return $json;


    }
}