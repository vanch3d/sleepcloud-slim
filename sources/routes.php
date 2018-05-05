<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:17
 */

use NVL\Controllers\APIController;
use NVL\Controllers\VisController;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;


/**
 * Local Middlewares
 */


/**
 * Web App Routes
 */
$app->group('',function() {

    // Main routes
    $this->get('/', function ($request, $response, $args) {
        return $this->view->render($response, 'pages/home.twig');
    })->setName('home');

    $this->get('/swagger', function ($request, $response, $args) {
        return $this->view->render($response, 'pages/swagger.ui.twig');
    })->setName('swagger.ui');

    $this->group('/diet',function() {

        $this->get('/time', VisController::class . ':showDietTime')->setName('vis.diet.time');
        $this->get('/staple/overall', VisController::class . ':showDietStaple')->setName('vis.diet.staple');
        $this->get('/staple', VisController::class . ':showDietStapleWeekly')->setName('vis.diet.staple-week');
        $this->get('/menu', VisController::class . ':showDietMenu')->setName('vis.diet.menu');
        $this->get('/intake', function ($request, $response, $args) {
            return $this->view->render($response, 'visualisation/diet/target-week.twig');
        })->setName('vis.diet.intake-week');

    })->add(function (Request $request, Response $response, callable $next) {
        // add application preference to globals
        $cfg = \NVL\App::getAppPreferences();
        $this->view->getEnvironment()->addGlobal("config", $cfg);
        return $next($request, $response);
    });

    $this->get('/calendar', VisController::class . ':showCalendar')->setName('vis.calendar');
    $this->get('/nights/{id}', VisController::class . ':showNight')->setName('vis.night');
    $this->get('/horizon', VisController::class . ':showHorizon')->setName('vis.horizon');

});

$app->group('/admin',function() {

    $this->get('', function ($request, $response, $args) {
        return $this->view->render($response, 'base.twig');
    })->setName('admin.home');

    $this->get('/diet', function ($request, $response, $args) {
        return $this->view->render($response, 'admin/food.categories.twig');
    })->setName('admin.diet');

});


$app->group('/api',function() {

    // swaggerUI
    $this->get('', APIController::class . ':getOpenAPI')->setName('api.swagger');

    // definitions
    $this->map(["GET","PUT"],'/definition/food', APIController::class . ':getOntologyFood')->setName('api.ontology.food');

    // raw data
    $this->get('/records/sleep', APIController::class . ':getSleepData')->setName('api.sleep.records');
    $this->get('/records/mood', APIController::class . ':getMoodData')->setName('api.mood.records');
    $this->get('/records/mood/tags', APIController::class . ':getMoodTags')->setName('api.mood.tags');
    $this->get('/records/diet', APIController::class . ':getDietData')->setName('api.diet.records');
    $this->get('/records/diet/nutrients', APIController::class . ':getNutrientData')->setName('api.diet.nutrient');

});

/**
 * Global Middlewares
 */

$app->add(new \RunTracy\Middlewares\TracyMiddleware($app));
$app->add(new \Slim\HttpCache\Cache('public', 86400));
$app->add(function (Request $request, Response $response, callable $next) {
    $route = $request->getAttribute('route');
    if (!empty($route)) {
        $name = $route->getName();
        $this->view->getEnvironment()->addGlobal("current_route", $name);
    }
    return $next($request, $response);
});



