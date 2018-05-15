<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:17
 */

use NVL\{
    Services\DataProvider,
    Services\DataWrangler,
    Support\Storage\Session
};
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------
// Twig
$container['view'] = function (\Slim\Container  $c) {
    $settings = $c->get('settings');
    $view = new Twig($settings['view']['template_path'], $settings['view']['twig']);
    // Add extensions
    $view->addExtension(new TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new Twig_Extension_Profiler($c['twig_profile']));

    //$view->getEnvironment()->addGlobal("current_path", $c["request"]->getUri()->getPath());
    $view->getEnvironment()->addGlobal("app_name", getenv('APP_NAME'));

    return $view;
};

// monolog
$container['logger'] = function (\Slim\Container $c) {
    $settings = $c->get('settings')['logger'];
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


/**
 * @param \Slim\Container $c
 * @return DataProvider\DropboxCached
 * @throws Exception
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['dropbox'] = function (\Slim\Container  $c) {
    $settings = $c->get('settings');
    return new DataProvider\DropboxCached($settings['dropbox'],$c->logger);
};


/**
 * @param \Slim\Container $c
 * @return DataWrangler\Mood
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['mood'] = function (\Slim\Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Mood($settings['dropbox'],$c->logger,$dropbox);
};

/**
 * @param \Slim\Container $c
 * @return DataWrangler\Sleep
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['sleep'] = function (\Slim\Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Sleep($settings['dropbox'],$c->logger,$dropbox);
};

/**
 * @param \Slim\Container $c
 * @return DataWrangler\Diet
 * @throws \Interop\Container\Exception\ContainerException
 */
$container['diet'] = function (\Slim\Container  $c) {
    $settings = $c->get('settings');
    $dropbox = $c->get('dropbox');
    return new DataWrangler\Diet($settings['dropbox'],$c->logger,$dropbox);
};
