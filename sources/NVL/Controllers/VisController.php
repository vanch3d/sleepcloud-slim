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
        return $this->getView()->render($response, 'visualisation/calendar.twig');

    }

    public function showNight(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/night.twig');
    }

    public function showHorizon(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/cubism.twig');
    }

    public function showDietTime(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/diet/time.twig');

    }

    public function showDietStaple(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/diet/staple.twig');
        //return $this->getView()->render($response, 'visualisation/diet/staple-week.twig');
    }

    public function showDietStapleWeekly(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/diet/staple-week.twig');
    }

    public function showDietMenu(Request $request, Response $response)
    {
        return $this->getView()->render($response, 'visualisation/diet/menu.twig');

    }

}