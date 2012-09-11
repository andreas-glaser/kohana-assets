<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets
{

    // config
    static protected $_config_defaults;
    protected $_config = array();
    // groups
    protected $groups = array(
        'js' => array(),
        'css' => array()
    );
    static $_instance;

    public static function instance()
    {
        if (!Kohana_Assets::$_instance)
        {
            Kohana_Assets::$_instance = $instance = new Kohana_Assets();
        } else
        {
            $instance = Kohana_Assets::$_instance;
        }

        return $instance;
    }

    public static function factory()
    {
        return new Kohana_Assets();
    }

    public function __construct(array $params = array())
    {

        if (is_null(self::$_config_defaults))
        {
            self::$_config_defaults = Kohana::$config->load('assets.default');
        }

        // override defaults
        $this->_config = arr::merge(self::$_config_defaults, $params);

        // add default group
        $this->js_group_add($this->_config['group_default']);
        $this->css_group_add($this->_config['group_default']);
    }

    public function js($location, $group_name = NULL, array $params = array())
    {
        // group name
        $group_name = !$group_name ? $this->_config['group_default'] : $group_name;

        // add asset
        $this->groups['js'][$group_name]->add(Kohana_Assets_Type_JS::factory($location, $params));
        return $this;
    }

    public function css($location, $group_name = NULL, array $params = array())
    {
        // group name
        $group_name = !$group_name ? $this->_config['group_default'] : $group_name;

        // add asset
        $this->groups['css'][$group_name]->add(Kohana_Assets_Type_CSS::factory($location, $params));
        return $this;
    }

    public function js_html($group_name = NULL)
    {
        // group name
        $group_name = !$group_name ? $this->_config['group_default'] : $group_name;

        $html = NULL;

        // get uncachable assets
        $uncacheable = $this->groups['js'][$group_name]->get_assets_uncacheable();


        foreach ($uncacheable AS $asset)
        {
            // get location
            $location_http = $url = $asset->get('location_http');

            // add version get var if local file
            if ($asset->is_local())
            {
                // get get var symbol
                $get_var_symbol = strpos($location_http, '?') ? '&' : '?';
                $url .= 'v=' . date('Y-m-d-H-i-s', $asset->get('date_modified'));
            }

            // add script line
            $html .= HTML::script($url) . "\n";
        }

        // get group cache
        $group_cache = $this->groups['js'][$group_name]->cache_assets_merge();

        // add script
        $html .= HTML::script('assets/js/' . $group_cache->filename . '?v=' . filemtime($group_cache->destination)) . "\n";

        return $html;
    }

    public function css_html($group_name = NULL)
    {
        // group name
        $group_name = !$group_name ? $this->_config['group_default'] : $group_name;

        $html = NULL;

        // get uncachable assets
        $uncacheable = $this->groups['css'][$group_name]->get_assets_uncacheable();

        foreach ($uncacheable AS $asset)
        {
            // get location
            $location_http = $url = $asset->get('location_http');

            // add version get var if local file
            if ($asset->is_local())
            {
                // get get var symbol
                $get_var_symbol = strpos($location_http, '?') ? '&' : '?';
                $url .= 'v=' . date('Y-m-d-H-i-s', $asset->get('date_modified'));
            }

            // add script line
            $html .= HTML::style($url) . "\n";
        }

        // get group cache
        $group_cache = $this->groups['css'][$group_name]->cache_assets_merge();

        // add script
        $html .= HTML::style('assets/css/' . $group_cache->filename . '?v=' . filemtime($group_cache->destination)) . "\n";

        return $html;
    }

    public function js_group_add($group_name)
    {
        // add group
        $this->groups['js'][$group_name] = new Kohana_Assets_Group('js', $group_name);
        return $this;
    }

    public function css_group_add($group_name)
    {
        // add group
        $this->groups['css'][$group_name] = new Kohana_Assets_Group('css', $group_name);
        return $this;
    }

}

