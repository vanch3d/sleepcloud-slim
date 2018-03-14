<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 13/03/2018
 * Time: 21:06
 */

namespace NVL\SleepCloud;

use Exception;
use http\Exception\BadConversionException;
use ReflectionClass;
use UnexpectedValueException;

abstract class Enum
{
    /**
     * Enum constructor.
     * @param $value
     * @throws Exception
     */
    final public function __construct($value)
    {
        try {
            $c = new ReflectionClass($this);
            if (!in_array($value, $c->getConstants()))
                throw new Exception("Unexpected value");
            $this->value = $value;
        } catch (\ReflectionException $e) {
        }

    }

    final public function __toString()
    {
        return $this->value;
    }

}

class Event extends Enum
{
    const ALARM_EARLIEST = "ALARM_EARLIEST";
    const ALARM_LATEST="ALARM_LATEST";
    const ALARM_SNOOZE="ALARM_SNOOZE";
    const ALARM_SNOOZE_AFTER_KILL="ALARM_SNOOZE_AFTER_KILL";
    const ALARM_DISMISS="ALARM_DISMISS";
    const TRACKING_PAUSED="TRACKING_PAUSED";
    const TRACKING_RESUMED="TRACKING_RESUMED";
    const TRACKING_STOPPED_BY_USER="TRACKING_STOPPED_BY_USER";
    const ALARM_STARTED="ALARM_STARTED";
    const SNORING="SNORING";
    const LOW_BATTERY="LOW_BATTERY";
    const DEEP_START="DEEP_START";
    const DEEP_END="DEEP_END";
    const LIGHT_START="LIGHT_START";
    const LIGHT_END="LIGHT_END";
    const REM_START="REM_START";
    const REM_END="REM_END";
    const BROKEN_START="BROKEN_START";
    const BROKEN_END="BROKEN_END";
    const AWAKE_START="AWAKE_START";
    const AWAKE_END="AWAKE_END";
}