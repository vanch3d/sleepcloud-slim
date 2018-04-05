<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 05/04/2018
 * Time: 20:54
 */

namespace NVL\Models\SleepCloud;
use Swagger\Annotations as OAS;

/**
 * @OAS\Schema(
 *     description="Sleep Record model",
 *     type="object",
 * )
 */
class Sleep
{
    /**
     * @OAS\Property()
     * @var string
     */
    public $Id;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Date;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Tz;

    /**
     * @OAS\Property()
     * @var string
     */
    public $From;

    /**
     * @OAS\Property()
     * @var string
     */
    public $To;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Sched;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Hours;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Rating;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Comment;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Framerate;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Snore;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Noise;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Cycles;

    /**
     * @OAS\Property()
     * @var string
     */
    public $DeepSleep;

    /**
     * @OAS\Property()
     * @var string
     */
    public $LenAdjust;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Geo;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Actigraph;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Events;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Levels;

}