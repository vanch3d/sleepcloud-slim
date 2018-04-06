<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 06/04/2018
 * Time: 13:43
 */

namespace NVL\Support;


use DateTime;
use DateTimeZone;

class JSONDateTime extends DateTime implements \JsonSerializable
{
    public static function createFromFormat($format, $time, DateTimeZone $timezone = null)
    {
        $f = parent::createFromFormat($format, $time, $timezone);
        return new JSONDateTime($f->format("c"));
    }

    public function jsonSerialize()
    {
        return $this->format("c");
    }
}
