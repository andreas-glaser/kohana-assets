<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Type_CSS extends Kohana_Asset
{

    protected $_processor;

    public static function factory($location, array $params = array(), $processor = NULL)
    {
        return new Kohana_Assets_Type_CSS($location, $params, $processor);
    }

    public function __construct($location, array $params = array(), $processor = NULL)
    {

        // execute parent
        parent::__construct('css', $location, $params);

        // processor
        if ($processor)
        {
            $this->_processor = $processor;
        } else
        {

            $this->_processor = $this->_config[$this->_type]['processor'];
        }
    }

    public function contents($return = FALSE, $refresh = FALSE)
    {
        // parent
        parent::contents($return, $refresh);

        // class
        $class = 'Kohana_Assets_Processor_CSS_' . $this->_processor;

        if (!class_exists($class))
        {
            throw new Kohana_Exception('CSS Processor has not been found');
        }

        // process kontet
        $this->_content = $class::process($this->_content);
        $this->_content = '/* DATE MODIFIED: ' . date('Y-m-d H:i:s', $this->_file['date_modified']) . ' (' . $this->_file['name'] . ') */' . "\n" . $this->_content . "\n\n";

        return $return ? $this->_content : $this;
    }

}