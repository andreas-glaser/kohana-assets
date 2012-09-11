<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Processor_JS_Jsmin extends Kohana_Assets_Processor
{

    static public function process($content)
    {
        // Include the processor
        include_once Kohana::find_file('vendor', 'jsmin/jsmin-1.1.1');

        return jsmin::minify($content);
    }

}