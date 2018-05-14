<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 20:19
 */

namespace NVL\Services;

use Psr\Log\LoggerInterface;

abstract class DataService
{
    private $config = null;
    private $logger = null;

    protected function log($level, $msg, $context)
    {
        //Debugger::barDump($context,$msg);
        if ($this->logger)
            $this->logger->log($level,$msg,$context);
    }

    abstract protected function validateConfig();


    public function __construct(array $settings, LoggerInterface $logger)
    {
        $this->config = $settings;
        $this->logger = $logger;

        $this->validateConfig();
    }


}