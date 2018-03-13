<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 13/03/2018
 * Time: 20:25
 */

namespace NVL\Controllers;


use Slim\Http\{
    Request, Response
};

class APIController extends Controller
{
    private $cache = DIR . '.cache/.sleep/data.csv';

    public function getSleepData(Request $request, Response $response)
    {
        return $response->withJson([], 200);
    }

}