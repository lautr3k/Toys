<?php namespace Toys;
/**
* Simply a module wrapper.
*
* @license   GPL
* @version   1.0.0
* @copyright 2015 Onl'Fait (http://www.onlfait.ch)
* @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
* @link      https://github.com/lautr3k/Toys
* @module    Toys
*/
class Module
{
    /**
    * Builder instance.
    *
    * @property  builder
    * @type      {Builder}
    */
    public $builder = null;

    /**
    * Namespace.
    *
    * @property  namespace
    * @type      {String}
    */
    public $namespace = null;

    /**
    * Id.
    *
    * @property  id
    * @type      {String}
    */
    public $id = null;

    /**
    * Name.
    *
    * @property  name
    * @type      {String}
    */
    public $name = null;

    /**
    * Class name.
    *
    * @property  class_name
    * @type      {String}
    */
    public $class_name = null;

    /**
    * Root path (project sources path).
    *
    * @property  root_path
    * @type      {String}
    */
    public $root_path = null;

    /**
    * Base path.
    *
    * @property  base_path
    * @type      {String}
    */
    public $base_path = null;

    /**
    * Sources path.
    *
    * @property  sources_path
    * @type      {String}
    */
    public $sources_path = null;

    /**
    * Release path.
    *
    * @property  release_path
    * @type      {String}
    */
    public $release_path = null;

    /**
    * Relative path to module files.
    *
    * @property  relpath
    * @type      {String}
    */
    public $relpath = null;

    /**
    * URL.
    *
    * @property  url
    * @type      {String}
    */
    public $url = null;

    /**
    * Default configuration.
    *
    * @static
    * @property defaults
    * @type     {stdClass}
    */
    public static $defaults = array
    (
        'main'     => 'module.js',
        'autoload' => false,
        'relpath'  => '',
        'require'  => [],
        'styles'   => ['styles/*'],
        'scripts'  => ['scripts/*'],
        'assets'   => ['assets/*'],
        'models'   => ['models/*'],
        'views'    => ['views/*'],
        'lang'     => ['lang/*']
    );

    /**
    * Configuration.
    *
    * @property config
    * @type     {stdClass}
    */
    public $config = [];

    /**
    * Files type to be autoloaded.
    *
    * @static
    * @property  autoload
    * @type      {stdClass}
    */
    public static $autoload = array
    (
        'styles',
        'scripts',
        'assets',
        'models',
        'views',
        'lang'
    );

    /**
    * Files list by type.
    *
    * @property  files
    * @type      {Array}
    */
    public $files = null;

    /**
    * Files compressor instance.
    *
    * @property  compressor
    * @type      {Compressor}
    */
    public $compressor = null;

    /**
    * Module class constructor.
    *
    * - At minimum a module is a directory with an `toys.json` file.
    * - You can overwrite all defaults properties listed below.
    *
    * __Internal defaults values :__
    *
    *       {
    *           "main"    : "modules.js",   // Main module file.
    *           "autoload": false,          // Must be loaded at startup ?
    *           "relpath" : "",             // Relative path to module files.
    *           "require" : [],             // Dependencies (namespaces).
    *           "styles"  : ["styles/*"],   // Stylesheets (.css).
    *           "scripts" : ["scripts/*"],  // Scripts (.js).
    *           "assets"  : ["assets/*"],   // Assets (images, fonts, etc...).
    *           "models"  : ["models/*"],   // Views models (.js).
    *           "views"   : ["views/*"],    // Views templates (.tpl).
    *           "lang"    : ["lang/*"]      // Languages textes (en.js, fr.js).
    *       }
    *
    * @constructor
    * @class Module
    * @param {Builder} $builder
    * @param {String} $namespace
    */
    public function __construct($builder, $namespace)
    {
        // Set builder instance
        $this->builder = $builder;

        // Set the namespace
        $this->namespace = strtolower($namespace);

        // Set the id (namespaces without separator)
        $this->id = str_replace('/', '', $this->namespace);

        // Set the name
        $this->name = basename($this->namespace);

        // Module class name (for modules autoloading)
        $this->class_name = Helper::classify($this->namespace);

        // Set root and base module paths
        $this->root_path = $builder->get_path('sources');
        $this->base_path = $this->root_path . '/' . $namespace;

        // Load the configuration
        $this->load_config();

        // Try to load files compressor
        if ($builder->compress)
        {
            $this->load_compressor();
        }

        // Set modules source path
        $this->sources_path = $this->base_path;

        if (! empty($this->relpath))
        {
             $this->sources_path .= '/' . $this->relpath;
        }

        // Set modules release path
        $this->release_path = $builder->get_path('release', $namespace);

        // Set module base url
        $rel_url   = $builder->compile ? '' : $this->relpath;
        $this->url = $builder->get_url('build', $namespace, $rel_url);

        // Auto load module files list
        foreach (self::$autoload as $type)
        {
            $this->files[$type] = $this->get_files_list($type);
        }

        // Path to main module file
        $main_file = $this->base_path . '/' . $this->config['main'];

        // If the main module file exist
        if (is_file($main_file))
        {
            // Normalize the file path
            $main_file = Helper::normalize_path($main_file);

            // Remove if alreday in the list
            // In case of the main file is on the 'scripts' directory
            unset($this->files['scripts'][$main_file]);

            // Append to scripts files list
            $this->files['scripts'][] = new File($this, 'scripts', $main_file);
        }
    }

    /**
    * Load and set configuration.
    *
    * @protected
    * @method load_config
    */
    protected function load_config()
    {
        // Path to module configuration file
        $config_file = $this->base_path . '/' . $this->builder->config['toys_filename'];

        // Set defaults configuration
        $this->config = self::$defaults;

        // Overwrite defaults values and set custom values
        foreach (Helper::load_json_file($config_file) as $index => $value)
        {
            $this->config[$index] = $value;
        }

        // If relative path is provided
        if (! empty($this->config['relpath']))
        {
            $this->relpath = Helper::normalize_path($this->config['relpath']);
        }
    }

    /**
    * Get all files by type in a flat tree.
    *
    * @protected
    * @method get_files_list
    * @param  {String} $type
    * @return {Array}
    */
    protected function get_files_list($type)
    {
        // Files list
        $files = array();

        // Set the search masks/files list
        $mask = $this->config[$type];

        // For each mask or file
        foreach ($mask as $path)
        {
            // Set absolute path
            $path = $this->sources_path . '/' . $path;

            // If it is a directory
            if (is_dir($path))
            {
                // Add wildcard
                $path .= '/*';
            }

            // For each path
            foreach (glob($path) as $path)
            {
                // Already in files list
                if (isset($files[$path]))
                {
                    continue;
                }

                // If it is a file
                if (is_file($path))
                {
                    // Add file object
                    $files[$path] = new File($this, $type, $path);
                }
                else
                {
                    // Get an flat files three (recursive)
                    $files += Helper::scan_path($path);
                }
            }
        }

        // Return the files found
        return $files;
    }

    /**
    * Load the module compressor if exist.
    *
    * @protected
    * @method load_compressor
    */
    protected function load_compressor()
    {
        // Possible PHP compressor file path
        $php_file = $this->base_path . '/' . $this->builder->config['compressor_filename'];

        // If compressor found
        if (is_file($php_file))
        {
            // Create the compressor class instance
            $compressor = new Compressor($php_file);

            // Initialize the compressor
            if (isset($compressor->initialize))
            {
                // Call the init. callback with this module at first param
                $result = call_user_func($compressor->initialize, $this);

                // Errors handler
                if (is_string($result))
                {
                    Error::raise($result);
                }
                else if (is_array($result))
                {
                    Error::raise($result[0], $result[1]);
                }
                else if ($result === false)
                {
                    // Disable compression
                    $compressor = null;
                }
            }

            // Set the module compressor
            $this->compressor = $compressor;
        }
    }
}
