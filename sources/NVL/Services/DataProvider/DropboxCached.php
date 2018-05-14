<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 15:08
 */

namespace NVL\Services\DataProvider;

use Psr\Log\LoggerInterface;

class DropboxCached implements ProviderInterface
{
    private $config = null;
    private $logger = null;
    private $cache = null;
    private $dropbox = null;


    /**
     * Check if config is fully defined
     * @throws \Exception
     */
    private function validateConfig()
    {
    }

    /**
     * DropboxCached constructor.
     * @param array           $settings
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(array $settings, LoggerInterface $logger)
    {
    }


    /**
     * @param string $dropboxfilename
     * @return Wrapper
     */
    public function getHash(string $dropboxfilename)
    {
        return null;
    }
}