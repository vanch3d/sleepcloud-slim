<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:17
 */

use Cartalyst\Sentinel\Users\IlluminateUserRepository;
use NVL\Controllers\APIController;
use NVL\Controllers\AuthController;
use NVL\Controllers\VisController;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;

// -----------------------------------------------------------------------------
// Local middleware
// -----------------------------------------------------------------------------

$configMiddleware = function (Request $request, Response $response, callable $next) {
    // add application preference to globals
    $cfg = \NVL\App::getAppPreferences();
    $this->view->getEnvironment()->addGlobal("config", $cfg);
    return $next($request, $response);
};

// -----------------------------------------------------------------------------
// Web App routes
// -----------------------------------------------------------------------------

// Open routes
$app->group('',function()
{
    $this->get('/user/register', AuthController::class . ':getRegister')->setName('user.register');
    $this->post('/user/register', AuthController::class . ':postRegister')->setName('user.register.post');
    $this->get('/user/login', AuthController::class . ':getLogin')->setName('user.login');
    $this->post('/user/login', AuthController::class . ':postLogin');
    $this->get('/user/logout', AuthController::class . ':getLogout')->setName('user.logout');

    $this->get('/', function ($request, $response, $args) {
        return $this->view->render($response, 'pages/home.twig');
    })->setName('home');

    $this->get('/swagger', function ($request, $response, $args) {
        return $this->view->render($response, 'pages/swagger.ui.twig');
    })->setName('swagger.ui');

});

// User-restricted routes
$app->group('',function() {
    // Main routes

    $this->group('/diet',function() {

        $this->get('/time', VisController::class . ':showDietTime')->setName('vis.diet.time');
        $this->get('/staple/overall', VisController::class . ':showDietStaple')->setName('vis.diet.staple');
        $this->get('/staple', VisController::class . ':showDietStapleWeekly')->setName('vis.diet.staple-week');
        $this->get('/menu', VisController::class . ':showDietMenu')->setName('vis.diet.menu');
        $this->get('/intake', function ($request, $response, $args) {
            return $this->view->render($response, 'visualisation/diet/target-week.twig');
        })->setName('vis.diet.intake-week');
        $this->get('/sequence', function ($request, $response, $args) {
            $show = ($request->getQueryParam("categories","false")!=="false");
            Debugger::barDump($show);
            return $this->view->render($response, 'visualisation/diet/sequence.twig',[
                "showCategories"=> $show?"true":"false"
            ]);
        })->setName('vis.diet.sequence');

    });

    $this->get('/calendar', VisController::class . ':showCalendar')->setName('vis.calendar');
    $this->get('/nights/{id}', VisController::class . ':showNight')->setName('vis.night');
    $this->get('/horizon', VisController::class . ':showHorizon')->setName('vis.horizon');

    $this->get('/activity', function ($request, $response, $args) {
        return $this->view->render($response, 'visualisation/activity.overview.twig');
    })->setName('vis.activity');

})->add($configMiddleware)
  ->add(new \NVL\Middleware\UserMiddleware($container));

// Admin-restricted routes
$app->group('/admin',function() {

    $this->get('', function ($request, $response, $args) {
        return $this->view->render($response, 'admin/main.twig');
    })->setName('admin.home');

    $this->get('/diet', function ($request, $response, $args) {
        return $this->view->render($response, 'admin/food.categories.twig');
    })->setName('admin.categories');

    $this->get('/fatsecret', function ($request, $response, $args) {

        /** @var \NVL\Services\Tools\FatSecret $fat */
        $fat = $this->get("fatsecret");
        $resp = $fat->searchIngredients("tomatoes");
        if ($resp)
        {
            Debugger::barDump($resp);
            $food = $fat->getIngredient($resp['foods']['food'][0]['food_id']);
            Debugger::barDump($food);

        }

        $settings = $this->settings['fatsecret'];
        Debugger::barDump($settings);
        return $this->view->render($response, 'admin/fatsecret.twig',[
            "fatsecret" => [ "key" => $settings['key']]
        ]);
    })->setName('admin.test');

})->add($configMiddleware)
  ->add(new \NVL\Middleware\AdminMiddleware($container));


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
    $this->get('/records/activity', APIController::class . ':getActivityData')->setName('api.health.activity');
    $this->get('/records/steps', APIController::class . ':getStepsData')->setName('api.health.steps');
});

/**
 * Global Middlewares
 */

$app->add(new \RunTracy\Middlewares\TracyMiddleware($app));

//$app->add(new \Slim\HttpCache\Cache('public', 86400));

$app->add(function (Request $request, Response $response, callable $next) {
    $route = $request->getAttribute('route');
    if (!empty($route)) {
        $name = $route->getName();
        $this->view->getEnvironment()->addGlobal("current_route", $name);
    }
    return $next($request, $response);
});

$app->add(function (Request $request, Response $response, callable $next) {
    /** @var \NVL\Container $this */
    /** @var IlluminateUserRepository $rep */
    /** @var \NVL\Models\User $instance */
    /** @var \Illuminate\Database\Eloquent\Builder $query */

    $count = $this->sentinel->getUserRepository()
        ->createModel()
        ->newQuery()
        ->count();

    $route = $request->getAttribute('route');
    if ($route !== null)
    {
        $routeName = $route->getName();
        // if no admin registered, redirect to registration
        if ($count===0 && ($routeName !== "user.register" && $routeName !== "user.register.post"))
        {
            $this->session->set("register.admin",true);
            return $response->withRedirect($this->router->pathFor('user.register'));
        }
    }
    return $next($request, $response);
});




