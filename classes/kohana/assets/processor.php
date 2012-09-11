<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_Assets_Processor
{

    public static function process($content)
    {
        return $content;
    }

}