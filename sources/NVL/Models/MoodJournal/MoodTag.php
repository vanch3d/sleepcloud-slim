<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 06/04/2018
 * Time: 01:54
 */

namespace NVL\Models\MoodJournal;
use Swagger\Annotations as OAS;


/**
 * @OAS\Schema(
 *     description="Mood Tag model",
 *     type="object"
 * )
 */
class MoodTag
{
    /**
     * @OAS\Property(
     * )
     * @var string
     */
    public $tagName;

    /**
     * @OAS\Property(
     * )
     * @var integer
     */
    public $frequency;

    /**
     * @OAS\Property(
     * )
     * @var integer
     */
    public $cachedLevelSum;
}