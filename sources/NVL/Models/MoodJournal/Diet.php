<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 06/04/2018
 * Time: 14:08
 */

namespace NVL\Models\MoodJournal;
use Swagger\Annotations as OAS;


/**
 * @OAS\Schema(
 *     description="Diet model",
 *     type="object"
 * )
 */
class Diet
{
    /**
     * @OAS\Property(
     * )
     * @var string
     */
    public $type;

    /**
     * @OAS\Property(
     *     format="date"
     * )
     * @var string
     */
    public $date;

    /**
     * @OAS\Property(
     *     @OAS\Items(
     *          type="string"
     *     )
     * )
     * @var array
     */
    public $items;
}