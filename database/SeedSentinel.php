<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Seeder;
use NVL\Auth\Auth;

/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 24/05/2018
 * Time: 09:34
 */

class SeedSentinel extends Seeder
{
    public function run()
    {
        Manager::table("roles")->insert([
            'slug' => Auth::ROLE_USER,
            'name' => Auth::ROLE_USER,
        ]);

        Manager::table("roles")->insert([
            'slug' => Auth::ROLE_ADMIN,
            'name' => Auth::ROLE_ADMIN,
        ]);
    }
}