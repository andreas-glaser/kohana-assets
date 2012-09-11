<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Assets_Inline
{

    protected static $cache = array(
        'js' => array(),
        'css' => array()
    );

    public static function start()
    {
        // Start the output buffer
        ob_start();
    }

    public static function stop($type = NULL)
    {
        // validate type
        self::_validate_type($type);

        // get output buffer
        $payload = ob_get_clean();

        // generate result
        $result = new stdClass();
        $result->type = $type;
        $result->payload_all = $payload;
        $class = 'Kohana_Assets_Inline_JS';
        $result->payload = $class::parse($payload);
        // $result->hash = self::hash($payload);
        // cache result
        self::$cache[$type][] = $result;

        return $result;
    }

    public static function merge($type = NULL)
    {
        // validate type
        self::_validate_type($type);

        $content = NULL;

        foreach (self::$cache[$type] AS $asset)
        {
            $content .= $asset->payload . "\n\n";
        }

        return $content;
    }

    public static function hash($content)
    {
        return md5($content);
    }

    public static function parse($content)
    {
        return trim($content);
    }

    protected static function _validate_type($type)
    {
        // validate type
        if ($type !== 'js' && $type !== 'css')
        {
            throw new Kohana_Exception('Invalid inline asset type');
        }
    }

}