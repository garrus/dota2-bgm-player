<?php

/**
 * Created by PhpStorm.
 * User: huyaowen
 * Date: 2017/3/6
 * Time: 15:18
 */
class Manifest
{
    public $albumName;
    public $namePrefix;

    private $_filename;

    private function __construct(){}

    /**
     * @param $filename
     * @return static
     */
    public static function createFromFile($filename)
    {
        $manifest = new static;
        $manifest->_filename = $filename;

        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            if (is_array($data)) {
                foreach ($data as $key => $val) {
                    if (strpos($key, '_') !== 0 && property_exists(__CLASS__, $key)) {
                        $manifest->$key = $val;
                    }
                }
            }
        }

        return $manifest;
    }
}