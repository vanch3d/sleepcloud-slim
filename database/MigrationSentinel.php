<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 23/05/2018
 * Time: 14:19
 */

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class MigrationSentinel
 * Duplicate the Sentinel migration class (Facade-free)
 * @see cartalyst/sentinel/src/migrations/2014_07_02_230147_migration_cartalyst_sentinel.php
 */
class MigrationSentinel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Manager::schema()->create('activations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            //$table->engine = 'InnoDB';
        });

        Manager::schema()->create('persistences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->timestamps();

            //$table->engine = 'InnoDB';
            $table->unique('code');
        });

        Manager::schema()->create('reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            //$table->engine = 'InnoDB';
        });

        Manager::schema()->create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->text('permissions')->nullable();
            $table->timestamps();

            //$table->engine = 'InnoDB';
            $table->unique('slug');
        });

        Manager::schema()->create('role_users', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->nullableTimestamps();

            //$table->engine = 'InnoDB';
            $table->primary(['user_id', 'role_id']);
        });

        Manager::schema()->create('throttle', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type');
            $table->string('ip')->nullable();
            $table->timestamps();

            //$table->engine = 'InnoDB';
            $table->index('user_id');
        });

        Manager::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->text('permissions')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();

            //$table->engine = 'InnoDB';
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Manager::schema()->drop('activations');
        Manager::schema()->drop('persistences');
        Manager::schema()->drop('reminders');
        Manager::schema()->drop('roles');
        Manager::schema()->drop('role_users');
        Manager::schema()->drop('throttle');
        Manager::schema()->drop('users');
    }

}