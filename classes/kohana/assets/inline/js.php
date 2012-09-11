<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Inline_JS extends Kohana_Assets_Inline
{

    public static function stop($type = 'js')
    {
        return parent::stop('js');
    }

    public static function merge($type = 'js')
    {
        return parent::merge('js');
    }

    public static function parse($content)
    {
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '$2', $content);
        return parent::parse($content);
    }

}