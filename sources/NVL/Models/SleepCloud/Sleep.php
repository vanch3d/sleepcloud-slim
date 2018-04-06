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
     * @OAS\Property(
     *     format="date"
     * )
     * @var string
     */
    public $Date;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Tz;

    /**
     * @OAS\Property(format="date")
     * @var string
     */
    public $From;

    /**
     * @OAS\Property(format="date")
     * @var string
     */
    public $To;

    /**
     * @OAS\Property(format="date")
     * @var string
     */
    public $Sched;

    /**
     * @OAS\Property()
     * @var float
     */
    public $Hours;

    /**
     * @OAS\Property()
     * @var float
     */
    public $Rating;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Comment;

    /**
     * @OAS\Property()
     * @var integer
     */
    public $Framerate;

    /**
     * @OAS\Property()
     * @var integer
     */
    public $Snore;

    /**
     * @OAS\Property()
     * @var float
     */
    public $Noise;

    /**
     * @OAS\Property()
     * @var integer
     */
    public $Cycles;

    /**
     * @OAS\Property()
     * @var float
     */
    public $DeepSleep;

    /**
     * @OAS\Property()
     * @var integer
     */
    public $LenAdjust;

    /**
     * @OAS\Property()
     * @var string
     */
    public $Geo;

    /**
     * @OAS\Property(
     *     @OAS\Items(
     *          ref="#/components/schemas/ActigraphRecord"
     *      )
     * )
     * @var array
     */
    public $Actigraph;

    /**
     * @OAS\Property(
     *     @OAS\Items(
     *          ref="#/components/schemas/EventRecord"
     *     ),
     * )
     * @var array
     */
    public $Events;

    /**
     * @OAS\Property(
     *     description="",
     *     @OAS\Items(
     *          type="number",
     *          format="float"
     *      )
     * )
     * @var array
     */
    public $Levels;

}