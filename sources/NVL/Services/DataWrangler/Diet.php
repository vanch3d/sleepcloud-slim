<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 15/05/2018
 * Time: 11:23
 */

namespace NVL\Services\DataWrangler;


class Diet extends Mood
{
    const BREAKFAST = "#breakfast";
    const LUNCH = "#lunch";
    const DINNER = "#dinner";
    const SNACKS = "#snacks";
    const SANDWICH = "#sandwich";

    protected $keywords = array(
        Diet::BREAKFAST,
        Diet::LUNCH,
        Diet::DINNER,
        Diet::SNACKS
    );

    /**
     * @return array
     */
    public function getData()
    {
        $mood = parent::getData();
        $data = [];

        foreach ($mood['data'] as $record) {
            $comment = $record['moodDescription']['comment'] ?? "";
            $arr = explode("\n", $comment);

            foreach ($arr as $item) {
                $match = array_intersect(explode(' ', $item), $this->keywords);
                if (count($match) > 0) {
                    $newRec = array(
                        'type' => $match[0],
                        'date' => $record['date'],
                        'items' => array()
                    );
                    // extract all tags (word starting with #)
                    $regex = '~(#\w+)~';
                    if (preg_match_all($regex, $item, $matches, PREG_PATTERN_ORDER)) {
                        foreach ($matches[1] as $word) {
                            if ($word !== $match[0])
                                $newRec['items'][] = ltrim($word, "#");
                        }
                    }
                    $data[] = $newRec;
                }
            }
        }

        return $data;
    }


    public function getFoodInfo()
    {

    }
}