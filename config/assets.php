<?php

defined('SYSPATH') OR die('No direct script access.');

return array(
    'default' => array(
        'group_default' => 'all',
        'cache' => array(
            'local' => TRUE,
            'remote' => FALSE
        ),
        'js' => array(
            'cache' => array(
                'enabled' => TRUE,
                'dir' => DOCROOT . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR,
                'max_lifetime' => 24 * 60 * 60,
            ),
            'processor' => 'jsmin'
        ),
        'css' => array(
            'cache' => array(
                'enabled' => TRUE,
                'dir' => DOCROOT . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR,
                'max_lifetime' => 24 * 60 * 60,
            ),
            'processor' => 'cssmin'
        )
    )
);