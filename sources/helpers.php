<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 22:52
 */


use RunTracy\Helpers\Profiler\Profiler;
use Tracy\Debugger;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
define('DIR', realpath(__DIR__ . '/../') . DS);
if (getenv('APP_DEBUG') === 'true')
{
    Debugger::$maxDepth = 5; // default: 3
    Debugger::$showLocation = true; // Shows all additional location information
    Debugger::enable(Debugger::DEVELOPMENT, DIR . '.logs');
    Profiler::enable();
}
