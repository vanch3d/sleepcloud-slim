<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 06/04/2018
 * Time: 01:14
 */

namespace NVL\Models\MoodJournal;
use Swagger\Annotations as OAS;


/**
 * @OAS\Schema(
 *     description="Mood Item model",
 *     type="object"
 * )
 */
class MoodRecord
{
    /**
     * @OAS\Property(
     *     format="date"
     * )
     * @var string
     */
    public $comment;

    /**
     * @OAS\Property()
     * @var boolean
     */
    public $wasEdited;

    /**
     * @OAS\Property(
     * )
     * @var string
     */
    public $thumbName;

    /**
     * @OAS\Property()
     * @var string
     */
    public $photoName;
}