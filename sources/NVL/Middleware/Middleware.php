<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 22/05/2018
 * Time: 16:04
 */

namespace NVL\Middleware;


use Interop\Container\ContainerInterface;
use NVL\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class Middleware
{
    /** @var Container $c*/
    protected $c;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->c = $container;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->c;
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    abstract function __invoke(Request $request, Response $response, callable $next);
}