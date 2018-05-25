<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 22/05/2018
 * Time: 16:44
 */

namespace NVL\Auth;


use Cartalyst\Sentinel\Sentinel;
use Cartalyst\Sentinel\Users\EloquentUser;
use Interop\Container\ContainerInterface;
use NVL\Models\Roles;

/**
 * Class Auth
 * A wrapper for authorisation-related tests and helpers
 * @package NVL\Auth
 *
 * @property Sentinel sentinel
 *
 */
class Auth
{
    // Application-specific roles
    // @todo[vanch3d] guest for APIs?
    const ROLE_ADMIN = "Admin";
    const ROLE_USER = "User";


    /** @var ContainerInterface */
    protected $c;

    public function __construct(ContainerInterface $container)
    {
        $this->c = $container;
    }

    public function __get($property)
    {
        if ($this->c->{$property}) {
            return $this->c->{$property};
        }
    }

    /**
     * Returns the currently logged in user, lazily checking for it.
     * @return \Cartalyst\Sentinel\Users\UserInterface
     */
    public function user()
    {
        return $this->sentinel->getUser();
    }

    /**
     * Checks to see if a user is logged in.
     * @return bool|\Cartalyst\Sentinel\Users\UserInterface
     */
    public function check()
    {
        return $this->sentinel->check();
    }

    /**
     * Checks if the user is in the Admin role.
     * @return bool|null
     */
    public function isAdmin()
    {
        /** @var EloquentUser $u */
        $u = $this->sentinel->getUser();
        if ($u) {
            return $u->inRole(static::ROLE_ADMIN);
        }
        return null;
    }

    /**
     * Get all pre-defined roles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function roles()
    {
        $roles = Roles::all();
        return $roles;
    }

}