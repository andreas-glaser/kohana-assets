<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Processor_CSS_Csscompressor extends Kohana_Assets_Processor
{

    static public function process($content)
    {
        // Include the processor
        include_once Kohana::find_file('vendor', 'minify_css_compressor/Compressor');

        return minify_css_compressor::process($content);
    }

}