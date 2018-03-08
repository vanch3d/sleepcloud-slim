<?php
/**
 * Created by PhpStorm.
 * User: vanch3d <nicolas.github@calques3d.org>
 * Date: 07/03/2018
 * Time: 23:19
 */

namespace NVL\Support\Storage;


use Countable;

class Session implements StorageInterface, Countable
{
    protected $storage;

    public function __construct($storage = 'default')
    {
        if (!isset($_SESSION[$storage])) {
            $_SESSION[$storage] = [];
        }

        $this->storage = $storage;
    }

    public function set($index, $value)
    {
        $_SESSION[$this->storage][$index] = $value;
    }

    public function get($index)
    {
        if (!$this->exists($index)) {
            return null;
        }

        return $_SESSION[$this->storage][$index];
    }

    public function exists($index)
    {
        return isset($_SESSION[$this->storage][$index]);
    }

    public function all()
    {
        return $_SESSION[$this->storage];
    }

    public function unset($index)
    {
        if ($this->exists($index)) {
            unset($_SESSION[$this->storage][$index]);
        }
    }

    public function clear()
    {
        unset($_SESSION[$this->storage]);
    }

    public function count()
    {
        return count($this->all());
    }
}
