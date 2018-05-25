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

class AdminMiddleware extends Middleware
{

    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    function __invoke(Request $request, Response $response, callable $next)
    {
        $isAdmin = false;
        if ($this->c->auth->check()) {
            $isAdmin = $this->getContainer()->auth->isAdmin();
        }
        if (!$isAdmin) {
            $this->c->flash->addMessage('error', 'You have no access right for this page.');
            return $response->withRedirect($this->getContainer()->router->pathFor('home'));
        }

        $response = $next($request, $response);
        return $response;
    }
}