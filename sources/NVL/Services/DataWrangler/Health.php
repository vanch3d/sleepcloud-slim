<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 18/05/2018
 * Time: 11:06
 */

namespace NVL\Services\DataWrangler;


use ArrayObject;
use Kunnu\Dropbox\Models\FileMetadata;
use Kunnu\Dropbox\Models\MetadataCollection;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\ResultSet;
use League\Csv\Statement;
use Monolog\Logger;
use NVL\Services\DataProvider\ProviderInterface;
use NVL\Services\DataService;
use Psr\Log\LoggerInterface;
use Tightenco\Collect\Support\Collection;
use Tracy\Debugger;
use ZipArchive;

/**
 * Class Health
 * @package NVL\Services\DataWrangler
 *
 * @see https://developer.samsung.com/health/server/api/data-types
 */
class Health extends DataService implements WranglerInterface
{
    const PACKAGE = "com.samsung.shealth";
    const DATA_STEPS = "step_daily_trend";
    const DATA_ACTIVITY = "activity.day_summary";

    public $provider;

    /**
     * Health constructor.
     * @param array             $settings
     * @param LoggerInterface   $logger
     * @param ProviderInterface $provider
     * @throws \Exception
     */
    public function __construct(array $settings, LoggerInterface $logger, ProviderInterface $provider)
    {
        parent::__construct($settings,$logger);
        $this->provider = $provider;
    }

    /**
     * Ensure that the configuration array is complete and valid to run the service
     * This is usually called in the service constructor
     * Return true if all config elements are present and correct
     * Throw and exception is not
     * @throws \Exception
     * @return boolean
     */
    protected function validateConfig()
    {
        return true;
    }

    /**
     * @return \League\Csv\ResultSet
     * @throws \Exception
     */
    public function getData()
    {
        return $this->getSteps();
    }

    /**
     * @return \League\Csv\ResultSet
     * @throws \Exception
     */
    public function getActivity()
    {
        // @todo[vanch3d] allow mapping to be changed by configuration
        $columns = [
            'longest_idle_time'     => ['time','longest_idle'],
            'score'                 => 'score',
            'extra_data'            => null,
            'goal'                  => 'goal',
            'calorie'               => 'calorie',
            'run_time'              => ['time','run'],
            'deviceuuid'            => null,
            'update_time'           => null,
            'longest_active_time'   => ['time','longest_active'],
            'day_time'              => 'day_time',
            'walk_time'             => ['time','walk'],
            'pkg_name'              => null,
            'active_time'           => ['time','active'],
            'distance'              => 'distance',
            'others_time'           => ['time','others'],
            'step_count'            => 'step_count',
            'datauuid'              => null,
            'create_time'           => 'create_time'
        ];

        return $this->extractFile(Health::DATA_ACTIVITY,$columns);
    }

    /**
     * @return ResultSet
     * @throws \Exception
     */
    public function getSteps()
    {
        // @todo[vanch3d] allow mapping to be changed by configuration
        $columns = [
            'source_pkg_name'   => null,
            'binning_data'      => null,
            'count'             => 'count',
            'calorie'           => 'calorie',
            'deviceuuid'        => null,
            'update_time'       => null,
            'source_type'       => 'source_type',
            'day_time'          => 'day_time',
            'speed'             => 'speed',
            'pkg_name'          => null,
            'distance'          => 'distance',
            'datauuid'          => null,
            'create_time'       => 'create_time'
        ];

        $filter = function (array $rec) {
            // @todo[vanch3d] date filtering should come from app config (session, cookies)
            $year = date('Y', strtotime($rec['create_time']));
            return ($year === "2018" && $rec['source_type'] === '0');
        };

        return $this->extractFile(Health::DATA_STEPS,$columns,$filter);
    }

    /**
     * @param string $filename
     * @param array  $headerMapping
     * @return ResultSet
     * @throws \Exception
     */
    protected function extractFile($filename, $headerMapping = null,$filter=null)
    {
        // get content of backup directory
        /** @var MetadataCollection $content */
        $content = $this->provider->listFolder($this->config['data']['health']);

        // get all files, filtered by extension (zip) and sorted by date
        /** @var Collection $all */
        $all = $content->getItems();
        $all = $all->filter(function($v) {
            $ext = pathinfo($v->name, PATHINFO_EXTENSION);
            return $v instanceof FileMetadata && $ext === "zip";
        })->sortByDesc(function($v){
            return strtotime($v->client_modified );
        });

        /** @var FileMetadata $file */
        $file = $all->first();
        $wrapper = $this->provider->getHash($file->getPathLower());

        // @todo[vanch3d] multiple files for same hash; append md5($filename)?
        if (file_exists($wrapper->cache . ".json"))
        {
            $this->log("INFO","json already cached",[$wrapper]);
            $json = json_decode(@file_get_contents($wrapper->cache . ".json"), true);
            return $json;
        }

        // file must be a zip archive
        $zip = new ZipArchive;
        $res = $zip->open($wrapper->cache);
        if ($res!== true)
        {
            $this->log(Logger::ERROR,"Archive cannot be opened", (array)$wrapper);
            throw new \Exception("Archive cannot be opened");
        }

        // build the fine name, eg
        // "201805142202/com.samsung.shealth.step_daily_trend.201805142202.csv";
        $base = basename($file->getName(),".zip");
        $pkg = "/" . Health::PACKAGE . ".";
        $ext =  "." . $base . ".csv";
        $csvFile = $base . $pkg . $filename . $ext;

        $csvString = $zip->getFromName($csvFile);
        if ($csvString === false)
        {
            $this->log(Logger::ERROR,"CSV cannot be extracted from archive", (array)$wrapper);
            throw new \Exception("CSV cannot be extracted from archive");
        }

        /** @var Reader $csv */
        $csv = Reader::createFromString($csvString);
        try {
            $fct = ($filter) ? $filter : function ($rec) {
                // @todo[vanch3d] date filtering should come from app config (session, cookies)
                $year = date('Y', strtotime($rec['create_time']));
                return $year === '2018';
            };

            $csv->setHeaderOffset(1);
            $stmt = (new Statement())
                ->offset(0)
                //->limit(10)
                ->where($fct)
                ->orderBy(function (array $ra, array $rb) {
                    // sort by descending order
                    $b = $ra['create_time'];
                    $a = $rb['create_time'];
                    return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
                });

            $records = $stmt->process($csv,($headerMapping)? array_keys($headerMapping): []);
            if ($headerMapping!==null)
            {
                // map the columns to the new ones
                $temp = array_map(function($elt) use ($headerMapping){
                    $newElt = [];
                    foreach ($headerMapping as $key => $value)
                    {
                        // mapping null => discard
                        if ($value === null) continue;
                        if (is_array($value) && !empty($value) && isset($elt[$key]))
                        {
                            // mapping array => combine in sub-array
                            $newElt[$value[0]][$value[1]] = $elt[$key];
                        }
                        else if (isset($elt[$value]))
                        {
                            // 1to1 mapping => copy
                            $newElt[$value] = $elt[$value];
                        }
                    }
                    return $newElt;
                },iterator_to_array($records, false));

                $obj = new ArrayObject( $temp );
                $records = new ResultSet($obj->getIterator(),$csv->getHeader());
                //Debugger::barDump(iterator_to_array($records));

            }

            // check for json compliance
            json_encode($records, JSON_PRETTY_PRINT|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);
            if (JSON_ERROR_NONE != json_last_error()) {
                throw new \Exception(json_last_error_msg());
            }

        } catch (Exception $e) {
            $this->log(Logger::ERROR,"CSV cannot be parsed", (array)$wrapper);
            throw new \Exception("CSV cannot be parsed");
        }

        return $records;
    }
}