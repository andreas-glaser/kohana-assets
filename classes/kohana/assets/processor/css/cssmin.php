<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Processor_CSS_CSSMIN extends Kohana_Assets_Processor
{

    /**
     * Process asset content
     *
     * @param   string  $content
     * @return  string
     */
    static public function process($content)
    {
        // Include the processor
        include_once Kohana::find_file('vendor', 'cssmin/cssmin-v1.0.1.b3');

        return cssmin::minify($content);
    }

}