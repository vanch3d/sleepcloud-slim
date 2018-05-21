<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 20:19
 */

namespace NVL\Services;

use Psr\Log\LoggerInterface;
use Tracy\Debugger;

abstract class DataService
{
    protected $config = null;
    protected $logger = null;

    protected function log($level, $msg, $context)
    {
        Debugger::barDump($context,$msg);
        if ($this->logger)
            $this->logger->log($level,$msg,$context);
    }

    /**
     * Ensure that the configuration array is complete and valid to run the service
     * This is usually called in the service constructor
     * Return true if all config elements are present and correct
     * Throw and exception is not
     * @throws \Exception
     * @return boolean
     */
    abstract protected function validateConfig();


    /**
     * DataService constructor.
     * @param array           $settings
     * @param LoggerInterface $logger
     * @throws \Exception
     */
    public function __construct(array $settings, LoggerInterface $logger)
    {
        $this->config = $settings;
        $this->logger = $logger;

        $this->validateConfig();
    }


}