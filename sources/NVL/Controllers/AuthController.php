<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 22/05/2018
 * Time: 15:56
 */

namespace NVL\Controllers;


use NVL\Auth\Auth;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;

class AuthController extends Controller
{
    public function getRegister(Request $request, Response $response)
    {
        // check if registration is for an admin
        $isRegAdmin = ($this->session->get("register.admin") === true);
        $options = array();

        if ($isRegAdmin) {
            $options["admin"] = $isRegAdmin;
            $this->flash->addMessageNow('info', 'First, you need to register yourself as an admin.');
        }
        return $this->view->render($response, 'user/register.twig',[
            "register" => $options]);
    }

    public function postRegister(Request $request, Response $response)
    {
        $credentials = [
            'email' => $request->getParam('email'),
            'password' => $request->getParam('password')
        ];
        $role = $request->getParam('role');
        $role = ($role===Auth::ROLE_ADMIN)? Auth::ROLE_ADMIN : Auth::ROLE_USER;

        // @todo[vanch3d] validation!
        /*$validation = $this->validator->validate($request, [
            'username' => v::noWhitespace()->notEmpty()->userAvailable(),
            'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
            'password' => v::noWhitespace()->notEmpty(),
            // 'password_confirm' => v::noWhitespace()->notEmpty()
        ]);
        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('user.register'));
        }*/

        try {
            $sRole = $this->sentinel->findRoleByName($role);
            $sUser = $this->sentinel->registerAndActivate($credentials);
            $sRole->users()->attach($sUser);

        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect($this->router->pathFor('user.register'));
        }

        $this->session->unset("register.admin");
        $this->flash->addMessage('success', 'You have been successfully registered. Login now.');
        return $response->withRedirect($this->router->pathFor('user.login'));
    }

    public function getLogin(Request $request, Response $response)
    {
        return $this->view->render($response, 'user/login.twig');
    }

    public function postLogin(Request $request, Response $response)
    {
        $credentials = [
            'email' => $request->getParam('email'),
            'password' => $request->getParam('password')
        ];

        try {
            $attempt = $this->sentinel->authenticate($credentials);
            if (!$attempt) throw new \Exception("error");

            $this->sentinel->login($attempt);
        }
        catch (\Exception $e) {
            $this->flash->addMessage('error', "There was an error with your login. Please check your credentials.");
            return $response->withRedirect($this->router->pathFor('user.login'));
        }

        return $response->withRedirect($this->router->pathFor('home'));

    }

    public function getLogout(Request $request, Response $response)
    {
        $this->sentinel->logout();
        return $response->withRedirect($this->router->pathFor('home'));
    }

}