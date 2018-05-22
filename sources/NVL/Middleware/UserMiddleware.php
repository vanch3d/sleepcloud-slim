<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 22/05/2018
 * Time: 16:07
 */

namespace NVL\Middleware;


use Slim\Http\Request;
use Slim\Http\Response;

class UserMiddleware extends Middleware
{

    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    function __invoke(Request $request, Response $response, callable $next)
    {
        // TODO: Implement __invoke() method.
        $response = $next($request, $response);
        return $response;
    }
}