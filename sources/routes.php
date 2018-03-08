<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:17
 */


/**
 * Local Middlewares
 */


/**
 * Web App Routes
 */
$app->group('/',function() {

    // Main routes
    $this->get('', function ($request, $response, $args) {
        return $this->view->render($response, '_defaultsite.twig');
    })->setName('home');


});


/**
 * Global Middlewares
 */

$app->add(new \RunTracy\Middlewares\TracyMiddleware($app));
$app->add(new \Slim\HttpCache\Cache('public', 86400));



