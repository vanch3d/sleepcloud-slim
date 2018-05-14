<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 15:09
 */

namespace NVL\Services\DataProvider;


interface ProviderInterface
{
    /**
     * @param string $filename
     * @return Wrapper
     */
    public function getHash(string $filename);

}