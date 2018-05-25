<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 25/05/2018
 * Time: 11:47
 */

namespace NVL;

use Cartalyst\Sentinel\Sentinel;
use Monolog\Logger;
use NVL\Auth\Auth;
use Slim\Flash\Messages;


/**
 * Class Container
 * Basic extension of Slim container, mostly used to define properties for the magic methods
 * @package NVL
 *
 * @property-read Sentinel sentinel
 * @property-read Auth auth
 * @property-read Messages flash
 * @property-read Logger logger
 */
class Container extends \Slim\Container
{

}
