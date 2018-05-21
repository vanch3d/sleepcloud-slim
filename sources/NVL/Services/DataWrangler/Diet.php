<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 15/05/2018
 * Time: 11:23
 */

namespace NVL\Services\DataWrangler;
use NVL\Services\DataProvider\ProviderInterface;

/**
 * Class Diet
 * Extract diet-related information from the iMood Journal, using the Mood wrangler.
 * @package NVL\Services\DataWrangler
 */
class Diet extends Mood
{
    const BREAKFAST = "#breakfast";
    const LUNCH = "#lunch";
    const DINNER = "#dinner";
    const SNACKS = "#snacks";

    // @todo[vanch3d] check if other keywords need to be identified
    const SANDWICH = "#sandwich";

    /**
     * @var array
     */
    protected $mealTypes = array(
        Diet::BREAKFAST,
        Diet::LUNCH,
        Diet::DINNER,
        Diet::SNACKS
    );

    /**
     * @var ProviderInterface $nutrients
     */
    protected $nutrients = null;

    /**
     * @param ProviderInterface $nutrients
     */
    public function setNutrientProvider(ProviderInterface $nutrients)
    {
        $this->nutrients = $nutrients;
    }

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
                $match = array_intersect(explode(' ', $item), $this->mealTypes);
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


    public function getExtendedData($withNutrients=false)
    {
        $json = $this->getData();
        foreach ($json as $diet)
            $data = array_merge($data ?? [], $diet['items']);
        $data = array_merge([], array_unique($data));

        // load food taxonomy
        // @todo[vanch3d] to add to configuration
        $fileCategories = DIR . "public/assets/food_category.json";
        $tempCat = json_decode(@file_get_contents($fileCategories), true) ?? [];


        $categories = [];
        $errors = [];

        // index food items
        foreach ($tempCat as $cat)
            $categories[$cat['id']] = $cat;

        // assign group to items extracted from diary and identify uncategorised
        foreach ($data as $foodItem) {
            if (isset($categories[$foodItem]) &&
                isset($categories[$foodItem]['group']) &&
                $categories[$foodItem]['group'] !== null) continue;

            $errors[] = $foodItem;
            $categories[$foodItem] = array(
                "id" => $foodItem,
                "group" => null
            );

        }

        // update the taxonomy with the new items
        if (!empty($errors->category))
            file_put_contents($fileCategories, json_encode($categories,JSON_PRETTY_PRINT));

        // build output
        //$json = array(
        //    'data' => $categories,
        //    'error' => $errors
        //);

        if ($withNutrients)
        {
            // if nutrients info service available, add them to data
            $categories = $this->getNutrients($categories);
        }

        // extract all attributed food categories
        $allCategories = array_values(array_unique(array_column($categories, "group")));

        $json = array(
            'data' => array_values($categories),
            'metadata' => array(
                'items' => $data,
                'categories' => $allCategories
            ),
            'error' => $errors
        );

        return $json;

    }

    /**
     * @param $wrap
     * @return mixed
     * @deprecated an alternative will be put in place through slim providers
     * @todo[vanch3d] check for FatSecret or Edamam provider
     */
    private function getNutrients($wrap)
    {
        if ($this->nutrients !== null)
        {
            $this->nutrients->getHash("");
        }
        return $wrap;
    }
}