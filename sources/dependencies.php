<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:17
 */

use NVL\Container;
use NVL\{
    Auth\Auth,
    Models\User,
    Services\DataProvider,
    Services\DataWrangler,
    Services\Tools,
    Support\Storage\Session
};
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

/** @var Container $container */
$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Setup Eloquent
// -----------------------------------------------------------------------------
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// -----------------------------------------------------------------------------
// Slim main service providers
// -----------------------------------------------------------------------------

// Twig
$container['view'] = function (Container $c) {

    $settings = $c->get('settings');
    $view = new Twig($settings['view']['template_path'], $settings['view']['twig']);
    // Add extensions
    $view->addExtension(new TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new Twig_Extension_Profiler($c['twig_profile']));

    $view->getEnvironment()->addGlobal("app_name", getenv('APP_NAME'));
    // @todo[vanch3d] check https://github.com/kanellov/slim-twig-flash
    $view->getEnvironment()->addGlobal('flash', $c->flash);
    $auth = [
        'check' => $c->auth->check(),
        'user' => $c->auth->user(),
        'isAdmin' => $c->auth->isAdmin(),
        'roles' => $c->auth->roles()
    ];
    $view->getEnvironment()->addGlobal('auth', $auth);
    \Tracy\Debugger::barDump($auth,"auth");
    return $view;
};

// monolog
$container['logger'] = function (Container $c) {
    $settings = $c->settings['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    $logger->pushHandler(
        new Monolog\Handler\RotatingFileHandler(
            $settings['path'],
            $settings['maxFiles'],
            $settings['level']
        )
    );
    return $logger;
};


// Flash messages
$container['flash'] = function () {
    return new Messages;
};

// Session
$container['session'] = function () {
    return new Session();
};

// Cache
$container['cache'] = function () {
    return new \Slim\HttpCache\CacheProvider();
};

$container['twig_profile'] = function () {
    return new Twig_Profiler_Profile();
};

// Eloquent DB
$container['db'] = function() use ($capsule) {
    return $capsule;
};

// Add Sentinel
$container['hasher'] = function () {
    return new Cartalyst\Sentinel\Hashing\BcryptHasher;
};

$container['dispatcher'] = function () {
    return new Illuminate\Events\Dispatcher;
};

$container['sentinel'] = function ($container) {
    $sentinel = (new \Cartalyst\Sentinel\Native\Facades\Sentinel())->getSentinel();
    $sentinel->setUserRepository(
        new \Cartalyst\Sentinel\Users\IlluminateUserRepository(
            $container['hasher'],
            $container['dispatcher'],
            User::class
        )
    );
    return $sentinel;
};

$container['auth'] = function($container) {
    return new Auth($container);
};


// -----------------------------------------------------------------------------
// Health Service Providers
// -----------------------------------------------------------------------------

/**
 * @param Container $c
 * @return DataProvider\DropboxCached
 * @throws Exception
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['dropbox'] = function (Container  $c) {
    $settings = $c->get('settings');
    return new DataProvider\DropboxCached($settings['dropbox'],$c->logger);
};


/**
 * @param Container $c
 * @return DataWrangler\Mood
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['mood'] = function (Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Mood($settings['dropbox'],$c->logger,$dropbox);
};

/**
 * @param Container $c
 * @return DataWrangler\Sleep
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['sleep'] = function (Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Sleep($settings['dropbox'],$c->logger,$dropbox);
};

/**
 * @param Container $c
 * @return DataWrangler\Diet
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['diet'] = function (Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    $diet =new DataWrangler\Diet($settings['dropbox'],$c->logger,$dropbox);
    try {
        // optionally add nutrient information
        $nutrients = $c->get('nutrients');
        $diet->setNutrientProvider($nutrients);
    } catch (\Interop\Container\Exception\ContainerException $e) {
    }
    return $diet;

};

/**
 * @param Container $c
 * @return DataWrangler\Health
 * @throws Exception
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['activity'] = function (Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Health($settings['dropbox'],$c->logger,$dropbox);
};

// -----------------------------------------------------------------------------
// External Tools
// -----------------------------------------------------------------------------

$container['fatsecret'] = function (Container  $c) {
    $settings = $c->get('settings');
    return new Tools\FatSecret($settings['fatsecret'],$c->logger);
};
