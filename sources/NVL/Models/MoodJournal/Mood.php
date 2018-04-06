<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 06/04/2018
 * Time: 01:10
 */

namespace NVL\Models\MoodJournal;
use Swagger\Annotations as OAS;

/**
 * Class Mood
 * @package NVL\Models\MoodJournal
 *
 *
 * @OAS\Schema(
 *     description="Mood Record model",
 *     type="object",
 * )
 */
class Mood
{
    /**
     * @OAS\Property(
     *     format="date"
     * )
     * @var string
     */
    public $date;

    /**
     * @OAS\Property()
     * @var integer
     */
    public $moodLevel;

    /**
     * @OAS\Property(
     *     ref="#/components/schemas/MoodRecord"
     * )
     * @var MoodRecord
     */
    public $moodDescription;

    /**
     * @OAS\Property(
     *     description="",
     *     @OAS\Items(
     *          type="string"
     *      )
     * )
     * @var array
     */
    public $moodTags;

}