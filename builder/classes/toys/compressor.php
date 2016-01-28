<?php namespace Toys;
/**
* User compressor wrapper.
*
* @license   GPL
* @version   1.0.0
* @copyright 2015 Onl'Fait (http://www.onlfait.ch)
* @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
* @link      https://github.com/lautr3k/Toys
* @module    Toys
*/
class Compressor
{
    /**
    * Compressor properties.
    *
    * @property compressor
    * @type     {Array}
    */
    public $compressor = [];

    /**
    * Compressor class constructor.
    *
    * @constructor
    * @class Compressor
    * @param {String} $file
    *
    * @example
    *     './src/namespace/module/compressor.php'
    *
    * @example
    *     return array
    *     (
    *         // Compressor initialization.
    *         'initialize' => function($module)
    *         {
    *             // do something...
    *
    *             // Continue without error
    *             return true; // null or void
    *
    *             // Disable compression for this module
    *             return false;
    *
    *             // Break compilation with {Error} message
    *             return 'Oups an [Unknown] error has occurred!';
    *
    *             // Break compilation with {Error} message and arguments
    *             return ['Oups an [%s] error has occurred!', ['Unknown']];
    *         },
    *
    *         // Assets compressor callback.
    *         'assets' => function($file)
    *         {
    *             // do something...
    *
    *             // Continue by copying the file to $file->release_path
    *             return true; // null or void
    *
    *             // Continue without copying the file
    *             return false;
    *
    *             // Or break with error (same as initialize example above).
    *         },
    *
    *         // Styles (scripts or views) compressor callback.
    *         'styles' => function($file)
    *         {
    *             // do something...
    *
    *             // Continue with minification
    *             return true; // null or void
    *
    *             // Continue without minification
    *             return false;
    *
    *             // Or break with error (same as initialize example above).
    *         },
    *
    *         // The same as 'styles' example.
    *         'scripts' => function($file) {},
    *
    *         // The same as 'styles' example.
    *         'views' => function($file) {}
    *     );
    */
    public function __construct($file)
    {
        $this->compressor = require($file);
    }

    /**
    * Compressor initialization.
    *
    * @method initialize
    * @param  {Module} $module
    * @return {Mixed}
    */
    public function initialize($module)
    {
        // Loaded dynamically from user provided 'compressor.php' file.
    }

    /**
    * Assets compressor callback.
    *
    * @method assets
    * @param  {File} $file
    * @return {Mixed}
    */
    public function assets($file)
    {
        // Loaded dynamically from user provided 'compressor.php' file.
    }

    /**
    * Stylesheet compressor callback.
    *
    * @method styles
    * @param  {File} $file
    * @return {Mixed}
    */
    public function styles($file)
    {
        // Loaded dynamically from user provided 'compressor.php' file.
    }

    /**
    * Scripts compressor callback.
    *
    * @method scripts
    * @param  {File} $file
    * @return {Mixed}
    */
    public function scripts($file)
    {
        // Loaded dynamically from user provided 'compressor.php' file.
    }

    /**
    * Views compressor callback.
    *
    * @method views
    * @param  {File} $file
    * @return {Mixed}
    */
    public function views($file)
    {
        // Loaded dynamically from user provided 'compressor.php' file.
    }

    /**
    * Return if the property is set.
    *
    * @protected
    * @method __isset
    * @param  {String} $key
    * @return {Boolean}
    */
    public function __isset($key)
    {
        return isset($this->compressor[$key]);
    }

    /**
    * Set the property.
    *
    * @protected
    * @method __set
    * @param {String} $key
    * @param {Mixed}  $value
    */
    public function __set($key, $value)
    {
        $this->compressor[$key] = $value;
    }

    /**
    * Return the property value.
    *
    * @protected
    * @method __get
    * @param  {String} $key
    * @return {Mixed}
    */
    public function __get($key)
    {
        return $this->compressor[$key];
    }

    /**
    * Try to call the property as function.
    *
    * @protected
    * @method __call
    * @param  {String} $key
    * @return {Mixed}
    */
    public function __call($method, $arguments)
    {
        return call_user_func_array($this->$method, $arguments);
    }
}
