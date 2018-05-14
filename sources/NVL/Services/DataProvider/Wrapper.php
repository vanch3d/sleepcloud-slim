<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 14/05/2018
 * Time: 16:59
 */

namespace NVL\Services\DataProvider;


class Wrapper
{
    public function cast($object)
    {
        if (is_array($object) || is_object($object)) {
            foreach ($object as $key => $value) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function __construct(string $file = null)
    {
        $this->dropboxFilename = $file;
    }

    /**
     * The original file in the remote storage
     * @var string
     */
    public $dropboxFilename = null;

    /**
     * The hash code of the latest version of the document
     * @var string
     */
    public $hash = null;

    /**
     * The (full) path of the locally cached version of the document
     * The name of the file should be the hash code
     * @var string
     */
    public $cache = null;

    /**
     * The path of the cached JSON object once parsed from the document
     * The name of the file should be the hash code with .json as extension
     * @var string
     */
    //public $json = null;

    /**
     * True if the current data bundle was downloaded from the remote storage,
     * False if retrieved from the cache
     * @var boolean
     */
    public $live = false;


    /**
     * The last exception (if any) thrown by the process
     * @var \Exception
     */
    public $error = null;

}