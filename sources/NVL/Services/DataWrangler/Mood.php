<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 20:15
 */

namespace NVL\Services\DataWrangler;


use DateTimeZone;
use NVL\Services\{
    DataProvider\ProviderInterface,
    DataService
};
use NVL\Support\JSONDateTime;
use Psr\Log\LoggerInterface;
use Tracy\Debugger;
use ZipArchive;

class Mood extends DataService implements WranglerInterface
{
    public $provider;

    public function __construct(array $settings, LoggerInterface $logger, ProviderInterface $provider)
    {
        parent::__construct($settings,$logger);
        $this->provider = $provider;
    }

    /**
     * @throws \Exception
     */
    protected function validateConfig()
    {
        if (!isset($this->config['data']['mood']))
            throw new \Exception("Mood data source missing");
    }

    public function getData()
    {
        $wrapper = $this->provider->getHash($this->config['data']['mood']);
        Debugger::barDump($wrapper);

        if (file_exists($wrapper->cache . ".json"))
        {
            $this->log("INFO","json already cached",[]);

            $json = json_decode(@file_get_contents($wrapper->cache . ".json"), true);
            return $json;

        }

        $zip = new ZipArchive;
        $res = $zip->open($wrapper->cache);
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

        $metadata['hash'] = $wrapper->hash;
        $metadata['tags'] = $tags;

        $json = [
            'data' => $data,
            'metadata' => $metadata
        ];

        file_put_contents($wrapper->cache . ".json", json_encode($json));

        return $json;
    }
}