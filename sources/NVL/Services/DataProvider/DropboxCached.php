<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 15:08
 */

namespace NVL\Services\DataProvider;

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use Kunnu\Dropbox\Models\FileMetadata;
use NVL\Services\DataService;
use Psr\Log\LoggerInterface;
use Tightenco\Collect\Support\Collection;
use Tracy\Debugger;

class DropboxCached extends DataService implements ProviderInterface
{
    protected $cache = null;
    protected $dropbox = null;

    /**
     * Check if config is fully defined
     * @throws \Exception
     */
    protected function validateConfig()
    {
        //Debugger::barDump($this->config);
        if (!isset($this->config['token']))
            throw new \Exception("Dropbox token missing");
        return true;
    }

    /**
     * DropboxCached constructor.
     * @param array           $settings
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(array $settings, LoggerInterface $logger)
    {
        parent::__construct($settings,$logger);
        $this->cache = $this->config['cache'];

        if (!file_exists($this->cache))
        {
            $this->log("DEBUG","creating cache",["cache"=>$this->cache]);
            mkdir($this->cache, 0755, true);
        }

        $this->validateConfig();

        $dbApp = new DropboxApp(
            null,
            null,
            $this->config['token']);
        $this->dropbox = new Dropbox($dbApp);
    }

    private function getLastCachedDocument(Wrapper $wrapper)
    {
        $sub = substr(md5($wrapper->dropboxFilename),0,3) . "/";
        $last = json_decode(@file_get_contents($this->cache . $sub . "last"), false) ?? null;

        if ($last && $wrapper->dropboxFilename === $last->dropboxFilename)
        {
            $last->error = $wrapper->error;
            $last->live = false;
            $wrapper = (new Wrapper)->cast($last);
        }


        return $wrapper;
    }

    /**
     * @param string $dropboxfilename
     * @return Wrapper
     */
    public function getHash(string $dropboxfilename)
    {
        $wrapper = new Wrapper($dropboxfilename);

        try {
            //Get File Metadata
            $fileMetadata = $this->dropbox->getMetadata($dropboxfilename);

            $hash = $fileMetadata->getData()['content_hash'];
            $wrapper->hash = $hash;

        } catch (DropboxClientException $e) {
            // Cannot download; check the last downloaded document
            $wrapper->error = $e;
            $wrapper = $this->getLastCachedDocument($wrapper);
            return $wrapper;
        }

        // check if hash is already cached
        $sub = substr(md5($wrapper->dropboxFilename),0,3) . "/";
        $localDoc = $this->cache . $sub . $wrapper->hash;
        if (!file_exists($this->cache . $sub))
        {
            mkdir($this->cache . $sub, 0755, true);
        }

        if (file_exists($localDoc)) {
            // document already in cache
            $wrapper->cache = $localDoc;
            $wrapper->live=false;
            return $wrapper;
        }

        try {
            // not cached, download
            $this->dropbox->download($dropboxfilename, $localDoc);
            $wrapper->cache = $localDoc;
            $wrapper->live=true;

            file_put_contents($this->cache . $sub . "last", json_encode($wrapper,JSON_PRETTY_PRINT));


        } catch (DropboxClientException $e) {
            // can't download ? you are out of luck
            $wrapper->cache = null;
            $wrapper->error = $e;
        }

        return $wrapper;
    }

    /**
     * @param string $pathname
     * @return \Kunnu\Dropbox\Models\MetadataCollection
     */
    public function listFolder(string $pathname)
    {
        return $ret = $this->dropbox->listFolder($pathname);
    }
}