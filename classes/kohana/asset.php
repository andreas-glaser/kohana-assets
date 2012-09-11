<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Asset
{

    protected $_config = array();
    protected $_is_remote;
    protected $_remote_head;
    protected $_file = array(
        'remote' => NULL,
        'name' => NULL,
        'location' => NULL,
        'location_http' => NULL,
        'extension' => NULL,
        'size' => NULL,
        'date_modified' => NULL,
        'date_expires' => NULL,
        'hash' => NULL
    );
    protected $_file_cache = array(
        'name' => NULL,
        'location' => NULL,
        'location_http' => NULL,
        'extension' => NULL,
        'size' => NULL,
        'date_modified' => NULL,
        'date_expires' => NULL,
    );
    protected $_cache_exists;
    protected $_type;
    protected $_content;
    protected $_cachable = TRUE;

    public function __construct($type, $location, array $params = array())
    {
        static $config = NULL;

        if (is_null($config))
        {
            $config = Kohana::$config->load('assets.default');
        }

        // http location
        $this->_file['location_http'] = $location;

        // override defaults with params
        $this->_config = arr::merge($config, $params);

        // set type
        $this->_type = $type;

        // enable / disable cache
        $this->_cachable = $this->_config[$this->_type]['cache']['enabled'];

        // is remote?
        $this->_is_remote = Valid::url($location);

        // make sure file exists
        if (!$this->_is_remote)
        {
            // enable / disable cache
            $this->_cachable = !$this->_cachable ? FALSE : $this->_config['cache']['local'];

            // get http location
            $this->_file['location_http'] = str_replace(DOCROOT, NULL, $location);

            // analyse file
            $this->_analyse_local($location);
        } else
        {
            // enable / disable cache
            $this->_cachable = !$this->_cachable ? FALSE : $this->_config['cache']['remote'];

            // trim leading slash
            $location = ltrim($location, '/');

            // get base url
            $url_base = url::base(TRUE, TRUE);

            // relative path?
            if (substr($location, 0, strlen($url_base)) == $url_base)
            {

                $this->_file['location_http'] = substr($location, strlen($url_base));

                // make absolute
                $location = Text::reduce_slashes(DOCROOT . $this->_file['location_http']);

                // set to 
                $this->_is_remote = FALSE;

                // analyse
                $this->_analyse_local($location);
            } else
            {
                // analyse
                $this->_analyse_remote($location);
            }
        }

        // copy definitions
        $this->_cache_filename = $this->_file['hash'] . '.' . $this->_type;
        $this->_cache_dest = $this->_config[$this->_type]['cache']['dir'] . $this->_cache_filename;

        // analyse cache if exists
        if ($this->_cachable)
        {
            if ($this->cache_exists())
            {
                $this->_analyse_file_cache();
            }
        }
    }

    /**
     * Enables Cache
     * @return \Kohana_Asset
     */
    public function cache_enable()
    {
        $this->_cacheable = TRUE;
        return $this;
    }

    /**
     * Disables Cache
     * @return \Kohana_Asset
     */
    public function cache_disable()
    {
        $this->_cachable = FALSE;
        return $this;
    }

    /**
     * Checks if cache is enaled
     */
    public function is_cachable()
    {
        return $this->_cachable;
    }

    public function cache_is_obsolete()
    {


        // check if cache is enabled to enforce clean coding
        $this->_cache_check_enabled();

        // make sure if remote caching is enabled
        if ($this->_is_remote && !$this->_config['cache']['remote'])
        {
            return FALSE;
        }

        // make sure caching is enabled for asset type
        if (!$this->_config[$this->_type]['cache']['enabled'])
        {
            return FALSE;
        }

        // has copy?
        if (!$this->cache_exists())
        {
            return TRUE;
        }

        // remote?
        if ($this->_is_remote)
        {
            if ($this->_file['date_expires'] >= time())
            {
                return true;
            }
        }

        // different file size?
        if ($this->_file['date_modified'] > $this->_file_cache['date_modified'])
        {
            // destroy cache
            $this->cache_destory();

            return true;
        }

        return false;
    }

    public function cache_exists()
    {
        // check if cache is enabled to enforce clean coding
        $this->_cache_check_enabled();

        // make sure if remote caching is enabled
        if ($this->_is_remote && !$this->_config['cache']['remote'])
        {
            return $this->_cache_exists = FALSE;
        }

        // make sure caching is enabled for asset type
        if (!$this->_config[$this->_type]['cache']['enabled'])
        {
            return $this->_cache_exists = FALSE;
        }

        $this->_cache_exists = file_exists($this->_cache_dest);

        // see if file exists        
        return $this->_cache_exists;
    }

    public function cache_destory()
    {
        if (file_exists($this->_cache_dest))
        {
            if (is_writable($this->_cache_dest))
            {
                unlink($this->_cache_dest);
                $this->_cache_exists = FALSE;
            }
        }

        return $this;
    }

    public function cache()
    {
        // check if cache is enabled to enforce clean coding
        $this->_cache_check_enabled();

        // make sure caching is necessary
        if (!$this->cache_is_obsolete())
        {
            return $this;
        }

        // make sure dir exists
        if (!is_dir($this->_config[$this->_type]['cache']['dir']))
        {
            throw new Kohana_Exception('Directory does not exist (:directory)', array(':directory' => $this->_cache_dir));
        }

        // make sure dir is writable
        if (!is_writable($this->_config[$this->_type]['cache']['dir']))
        {
            throw new Kohana_Exception('Directory is no writable (:directory)', array(':directory' => $this->_cache_dir));
        }

        // save file
        $this->save($this->_config[$this->_type]['cache']['dir'], $this->_cache_filename, TRUE);

        // analyse file
        $this->_analyse_file_cache();

        return $this;
    }

    public function save($directory, $filename = NULL, $override = TRUE)
    {
        // trim trainling directory separator
        $directory = rtrim($directory, DS);

        // get filename
        if (!$filename)
        {
            $filename = $this->_file['hash'] . '.' . $this->_type;
        }

        // full destination
        $destination = $directory . DS . $filename;

        // validate destination
        $dir = new SplFileInfo($directory);

        // Make sure it's a directory
        if (!$dir->isDir())
        {
            throw new Kohana_Exception('Directory is not a directory (:directory)', array(':directory' => $directory));
        }

        // make sure it's writeable
        if (!$dir->isWritable())
        {
            throw new Kohana_Exception('Directory is not writable (:directory)', array(':directory' => $directory));
        }

        // make sure there isn't a file with the name name
        if (!$override)
        {
            if (file_exists($destination))
            {
                throw new Kohana_Exception('A file with the same name already exists (:destination)', array(':destination' => $destination));
            }
        }

        // get content
        $this->contents();

        // open and/or create file if necessary
        $handle = fopen($destination, 'w+');

        // write content
        fwrite($handle, $this->_content);

        // close file
        fclose($handle);

        return $this;
    }

    public function contents($return = FALSE, $refresh = FALSE)
    {

        // get content if necessary
        if (!$this->_content || $refresh)
        {
            $this->_content = file_get_contents($this->_file['location']);
        }

        return $return ? $this->_content : $this;
    }

    protected function _analyse_file_cache($location = NULL)
    {
        // check if cache is enabled to enforce clean coding
        $this->_cache_check_enabled();

        $location = is_null($location) ? $this->_cache_dest : $location;

        if ($this->cache_exists())
        {
            // analyse file
            $file = new SplFileInfo($location);

            $this->_file_cache['name'] = $file->getFilename();
            $this->_file_cache['extension'] = $file->getExtension();
            $this->_file_cache['location'] = $location;
            $this->_file_cache['location_http'] = str_replace(DOCROOT, NULL, $location);
            $this->_file_cache['size'] = $file->getSize();
            $this->_file_cache['date_modified'] = $file->getCTime();
            $this->_file_cache['date_expires'] = $this->_file_cache['date_modified'] + $this->_config[$this->_type]['cache']['max_lifetime'];
        }
    }

    protected function _analyse_local($location)
    {
        // relative path?
        if (substr($location, 0, strlen(DOCROOT)) != DOCROOT)
        {

            // make absolute
            $location = Text::reduce_slashes(DOCROOT . $location);
        }

        // analyse file
        $file = new SplFileInfo($location);

        if (!$file->isFile())
        {
            throw new Kohana_Exception('File could not be found (:location)', array(':location' => $location));
        }

        if (!$file->isReadable())
        {
            throw new Kohana_Exception('File could not be read (:location)', array(':location' => $location));
        }

        $this->_file['remote'] = FALSE;
        $this->_file['name'] = $file->getFilename();
        $this->_file['extension'] = $file->getExtension();
        $this->_file['location'] = $location;
        $this->_file['size'] = $file->getSize();
        $this->_file['date_modified'] = $file->getCTime();
        $this->_file['date_expires'] = $this->_file['date_modified'] + $this->_config[$this->_type]['cache']['max_lifetime'];
        $this->_file['hash'] = md5($location);
    }

    protected function _analyse_remote($location)
    {
        $this->_file['remote'] = TRUE;
        $this->_file['name'] = NULL; //file::get_filename($location);
        $this->_file['extension'] = NULL; //file::ext_by_filename($location);
        $this->_file['location'] = $location;
        $this->_file['date_modified'] = NULL;
        $this->_file['hash'] = md5($location);

        // $this->remote_expires();
        //$this->remote_filesize();
    }

    public function cache_get($key)
    {
        // check if cache is enabled to enforce clean coding
        $this->_cache_check_enabled();

        if (!$this->_cache_exists)
        {
            return NULL;
        }

        return $this->_file_cache[$key];
    }

    public function get($key)
    {
        return $this->_file[$key];
    }

    public function is_local()
    {
        return !$this->_is_remote;
    }

    public function is_remote()
    {
        return $this->_is_remote;
    }

    public function type()
    {
        return $this->_type;
    }

    protected function _cache_check_enabled()
    {
        if (!$this->_cachable)
        {
            throw new Kohana_Exception('Cache has been disabled for this type of asset. Don\'t try to cache it! (Remote: :remote - Type: :type', array(':remote' => $this->_is_remote ? 'TRUE' : 'FALSE', ':type' => $this->_type));
        }
    }

}
