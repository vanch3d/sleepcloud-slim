<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 05/04/2018
 * Time: 17:13
 */

require_once __DIR__ . './../vendor/autoload.php';

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

try {
    (new Dotenv(__DIR__ . './../'))->load();
} catch (InvalidPathException $e) {
    error_log('[ERROR] config : ' . $e->getMessage());
    die();
}

try {
    $config = require_once __DIR__ . './../sources/config.php';
    $config = $config['settings']['swagger'];
    if ($config === null)
        throw new Exception('option \'settings/swagger\' does not exist');

    define("SWAGGER_VERSION",$config['version']);
    define("API_NAME","Swagger " . $config['api']['name']);
    define("API_VERSION",$config['api']['version']);

    error_log("=> Parsing " . API_NAME . " / " . API_VERSION);

} catch (Exception $e) {
    error_log('[ERROR] config : ' . $e->getMessage());
    die();
}
