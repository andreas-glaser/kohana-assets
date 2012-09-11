<?php

defined('SYSPATH') or die('No direct script access.');

class Kohana_Assets_Group
{

    protected $assets = array();
    protected $_type, $_name, $_id;
    protected $_contents;
    // static config defaults to prevent excessive reloading
    protected static $_config_defaults;

    public function __construct($type, $name = NULL)
    {
        // load config if necessary
        if (is_null(self::$_config_defaults))
        {
            self::$_config_defaults = Kohana::$config->load('assets.default');
        }

        $this->_type = $type;
        $this->_name = $name;
    }

    public function add($asset)
    {
        // validate asset
        if (!$asset instanceof Kohana_Asset)
        {
            throw new Kohana_Exception('Asset has to be an instance of Kohana_Assets');
        }

        // validate asset type
        if ($asset->type() !== $this->_type)
        {
            throw new Kohana_Exception('Invalid asset type. Expected: :type_expected Provided: :type_provided', array(':type_expected' => $this->_type, ':type_provided' => $asset->type()));
        }

        // make sure that there is no duplicate
        foreach ($this->assets AS $record)
        {
            if ($record == $asset)
            {
                return $this;
            }
        }

        // add asset to array
        $this->assets[] = $asset;

        // destory id
        $this->_id = $this->_contents = NULL;

        return $this;
    }

    public function process()
    {
        // cache everything
        $this->cache_assets_merged();
    }

    public function cache_assets_merge()
    {
        // result
        $result = new stdClass();
        $result->filename = $this->cache_filename();
        $result->directory = self::$_config_defaults[$this->_type]['cache']['dir'];
        $result->destination = $result->directory . $result->filename;

        $refreshed = FALSE;

        // refresh if necessary
        if ($this->cache_is_obsolete(TRUE))
        {
            $this->cache_assets_refresh();
            $refreshed = TRUE;
        }

        // only store is cache is obsolete
        if ($refreshed || !file_exists($result->destination))
        {
            // get content
            $content = $this->contents_merge();

            // open and/or create file if necessary
            $handle = fopen($result->destination, 'w+');

            // write content
            fwrite($handle, $content);

            // close file
            fclose($handle);

            // generate control file
            $content_control = NULL;

            foreach ($this->assets AS $asset)
            {
                if ($asset->is_cachable())
                {
                    $content_control .= $asset->get('hash') . '-' . $asset->cache_get('date_modified') . text::linefeed();
                }
            }

            $content_control = trim($content_control);

            // open and/or create file if necessary
            $handle_control = fopen($result->destination . '.control', 'w+');

            // write content
            fwrite($handle_control, $content_control);

            // close file
            fclose($handle_control);
        }

        return $result;
    }

    /**
     * Refreshes cache of assets if necessary
     * 
     * @return \Kohana_Assets_Group
     */
    public function cache_assets_refresh()
    {
        foreach ($this->assets AS $asset)
        {
            if ($asset->is_cachable())
            {
                $asset->cache();
            }
        }

        return $this;
    }

    /**
     * Checks wheather an asset has been updated which makes the group cache obsolete
     * 
     * @param type $delete_if_obsolete
     * @return boolean
     */
    public function cache_is_obsolete($delete_if_obsolete = TRUE)
    {
        if (!$this->cache_exists())
        {
            return TRUE;
        }

        $is_obsolete = FALSE;

        foreach ($this->assets AS $asset)
        {
            if ($asset->is_cachable())
            {

                if ($asset->cache_is_obsolete())
                {

                    $is_obsolete = TRUE;
                }
            }
        }

        // validate cache head
        $filename = $this->cache_filename();
        $destination = self::$_config_defaults[$this->_type]['cache']['dir'] . $filename;

        // read control file
        $lines = file($destination . '.control', FILE_IGNORE_NEW_LINES);

        foreach ($lines AS $line)
        {
            if (!$line)
            {
                continue;
            }
            try
            {
                $pieces = explode('-', $line);

                if (!isset($pieces[1]))
                {
                    continue;
                }

                foreach ($this->assets AS $asset)
                {
                    if ($pieces[0] == $asset->get('hash'))
                    {
                        if ($asset->is_cachable())
                        {
                            if ($pieces[1] < $asset->cache_get('date_modified'))
                            {
                                $is_obsolete = TRUE;
                            }
                        }
                    }
                }
            } catch (Exception $e)
            {
                throw new Kohana_Exception($e);
            }
        }

        // delete cache if necessary
        if ($is_obsolete && $delete_if_obsolete)
        {
            $this->cache_delete();
        }

        return $is_obsolete;
    }

    /**
     * Deletes group cache
     * 
     * @return \Kohana_Assets_Group
     * @throws Kohana_Exception
     */
    public function cache_delete()
    {
        // filename
        $filename = $this->cache_filename();
        $destination = self::$_config_defaults[$this->_type]['cache']['dir'] . $filename;

        // read file
        $file = new SplFileInfo($destination);

        if ($file->isFile())
        {
            // make sure file is writable
            if (!$file->isWritable())
            {
                throw new Kohana_Exception('Cache file is not writable / deletable (:destination)', array(':destination' => $destination));
            }

            // delete file
            unlink($destination);
        }

        // read file
        $file_control = new SplFileInfo($destination . '.control');

        if ($file_control->isFile())
        {
            // make sure file is writable
            if (!$file_control->isWritable())
            {
                throw new Kohana_Exception('Cache Control file is not writable / deletable (:destination)', array(':destination' => $destination));
            }

            // delete file
            unlink($destination . '.control');
        }

        return $this;
    }

    /**
     * Merges contens of cachable assets
     * 
     * @param boolean $refresh
     * @return string
     */
    public function contents_merge($refresh = FALSE)
    {

        if (!$this->_contents || $refresh)
        {

            // content
            $content = NULL;

            foreach ($this->assets AS $asset)
            {

                if ($asset->is_cachable())
                {
                    $content .= $asset->contents(TRUE);
                }
            }

            // set content
            $this->_contents = $content;
        }

        return $this->_contents;
    }

    /**
     * Returns unique ID for group.
     * The ID is based on the asset's hash
     * 
     * @return sting
     */
    public function id()
    {
        if (!$this->_id)
        {
            $id = NULL;
            foreach ($this->assets AS $asset)
            {
                $id .= $asset->get('hash');
            }
            $this->_id = md5($id);
        }

        return $this->_id;
    }

    public function cache_exists()
    {
        $filename = $this->cache_filename();
        $destination = self::$_config_defaults[$this->_type]['cache']['dir'] . $filename;
        return (file_exists($destination) && file_exists($destination . '.control'));
    }

    /**
     * Get's unique cache filename
     * 
     * @return string
     */
    public function cache_filename()
    {
        return $this->_name . '_' . $this->id() . '.' . $this->_type;
    }

    /**
     * Returns all attached assets
     * 
     * @return array
     */
    public function get_assets_all()
    {
        return $this->assets;
    }

    /**
     * Returns uncacheable assets
     * @return array
     */
    public function get_assets_uncacheable()
    {
        $assets = array();
        foreach ($this->assets AS $asset)
        {
            if (!$asset->is_cachable())
            {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

}