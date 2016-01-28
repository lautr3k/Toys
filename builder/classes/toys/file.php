<?php namespace Toys;
/**
* Simply a file wrapper.
*
* @license   GPL
* @version   1.0.0
* @copyright 2015 Onl'Fait (http://www.onlfait.ch)
* @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
* @link      https://github.com/lautr3k/Toys
* @module    Toys
*/
class File
{
    /**
    * Module instance.
    *
    * @property module
    * @type     {Module}
    */
    public $module = null;

    /**
    * Type.
    *
    * @property type
    * @type     {String}
    */
    public $type = null;

    /**
    * Name.
    *
    * @property name
    * @type     {String}
    */
    public $name = null;

    /**
    * Id.
    *
    * @property domid
    * @type     {String}
    */
    public $id = null;

    /**
    * Relative path (from module).
    *
    * @property relative_path
    * @type     {String}
    */
    public $relative_path = null;

    /**
    * URL.
    *
    * @property url
    * @type     {String}
    */
    public $url = null;

    /**
    * Path.
    *
    * @property path
    * @type     {String}
    */
    public $path = null;

    /**
    * Release path.
    *
    * @property release_path
    * @type     {String}
    */
    public $release_path = null;

    /**
    * File class constructor.
    *
    * @constructor
    * @class File
    * @param {Module} $module
    * @param {String} $type
    * @param {String} $path
    */
    public function __construct($module, $type, $path)
    {
        // Set module instance
        $this->module = $module;

        // Set the type
        $this->type = $type;

        // Set the path
        $this->path = $path;

        // Set the name
        $this->name = basename($path);

        // Set the id (kebab-case)
        $id = preg_replace('|[^a-zA-Z0-9_\-]+|', '-', $this->name);
        $this->id = strtolower($module->id . '-' . $id);

        // Relative path
        $this->relative_path = str_replace($module->sources_path . '/', '', $path);

        // Release path
        $this->release_path = $module->release_path . '/' . $this->relative_path;

        // URL
        $this->url = $module->url . '/' . $this->relative_path;

        // Is an compressible file
        $this->compressible = in_array($type, ['styles', 'scripts', 'models', 'views']);

        // File contents
        $this->data = null;
    }

    /**
    * Get, set and return the normalized file contents.
    *
    * @method get_data
    * @return {String}
    */
    public function get_data()
    {
        return $this->data = Helper::get_file_contents($this->path);
    }
}
