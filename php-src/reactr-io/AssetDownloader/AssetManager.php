<?php

namespace ReactrIO\AssetDownloader;

class AssetManager
{
    protected static $assets = array();

    protected function __construct()
    {

    }

    protected static function _to_key($keys)
    {
        return json_encode($keys);
    }

    public static function add($keys, $data=array())
    {
        if (!is_array($data) || !isset($data['url'])) {
            throw E_AssetDownloader::create("To add an asset, you must specify a url key in the data argument", array(
                'keys'  => $keys,
                'data'  => $data
            ));
        }

        if (!isset($data['filename'])) $data['filename'] = basename($data['url']);

        if (!isset(self::$assets)) self::$assets = array();
        self::$assets[self::_to_key($keys)] = $data;
    }

    public static function remove($keys)
    {
        if (!isset(self::$assets)) self::$assets = array();
        unset(self::$assets[self::_to_key($keys)]);
    }

    public static function get($keys=array())
    {
        $key = self::_to_key($keys);
        if (!isset(self::$assets[$key])) {
            throw E_AssetDownloader::create("No asset configured for the keys provided", array(
                'keys' => $keys
            ));
        }
        return self::$assets[$key];
    }
}