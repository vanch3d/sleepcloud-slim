<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 22/05/2018
 * Time: 16:41
 */

namespace NVL\Models;


use Cartalyst\Sentinel\Users\EloquentUser as SentinelUser;

class User extends SentinelUser
{
    protected $table = 'users';

    protected $fillable = [
        'email',
        'password',
        'permissions'
    ];
    protected $loginNames = ['email'];

}