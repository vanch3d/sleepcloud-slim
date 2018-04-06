<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 05/04/2018
 * Time: 22:53
 */

namespace NVL\Models\SleepCloud;
use Swagger\Annotations as OAS;

/**
 * @OAS\Schema(
 *     description="Event Record model",
 *     type="object",
 * )
 */
class EventRecord
{

    /**
     * @OAS\Property(
     *     enum={
     *      "ALARM_EARLIEST"
     *     },
     *     description=""
     * )
     * @var string
     */
    public $id;

    /**
     * @OAS\Property(
     *     format="date"
     * )
     * @var string
     */
    public $date;
}