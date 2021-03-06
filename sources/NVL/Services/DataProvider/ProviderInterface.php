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
     * /
     * @todo[vanch3d] Rename the method into something more accurate
     * @@todo[vanch3d] OR try to refactor the class to make the workflow more explicit
     */
    public function getHash(string $filename);

    /**
     * @param string $pathname
     * @return \Kunnu\Dropbox\Models\MetadataCollection
     */
    public function listFolder(string $pathname);

}