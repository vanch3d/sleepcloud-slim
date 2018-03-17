<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 15/03/2018
 * Time: 20:56
 */

namespace NVL\Controllers;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;

/**
 * Class VisController
 * @package NVL\Controllers
 */
class VisController extends Controller
{

    public function showCalendar(Request $request, Response $response)
    {
        $rr = $request->getAttribute('route') ;
        //Debugger::barDump($rr);
        return $this->getView()->render($response, 'visualisation/calendar.twig');

    }
}