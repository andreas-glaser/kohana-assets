<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Processor_JS_Jsminplus extends Kohana_Assets_Processor
{

    static public function process($content)
    {
        // Include the processor
        include_once Kohana::find_file('vendor', 'jsminplus/jsminplus');

        return jsminplus::minify($content);
    }

}

