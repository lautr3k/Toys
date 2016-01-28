<?php namespace Toys;
/**
* ## Toys - Project builder.
*
* This library is responsible to fetch and compile a Toys.js project,
* and provide an live in browser interface for pleasant developement.
*
*  **Usage:**
*
*      // Create the project builder instance
*      $builder = new Builder('./sources', './output');
*
*      // Build the project
*      $builder->build();
*
*      // Print the built output
*      $builder->display();
*
*      // Or get the output buffer as string
*      $output = $builder->render();
*
*   **In browser:**
*
*   ex.: http://localhost/project/builder/?compile&clean
*
*       ?compile  -> compile the project.
*       ?compress -> compile and compress the project.
*       ?clean    -> clean release and cache directory.
*       ?nocache  -> compile/compress without cache.
*
*
*
* @license   GPL
* @version   1.0.0
* @copyright 2015 Onl'Fait (http://www.onlfait.ch)
* @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
* @link      https://github.com/lautr3k/Toys
* @module    Toys
* @main      Toys
*/
class Builder
{
    /**
    * Compilation mode.
    *
    * @protected
    * @property compile
    * @type {Boolean}
    */
    protected $compile = false;

    /**
    * Compression mode.
    *
    * @protected
    * @property compress
    * @type {Boolean}
    */
    protected $compress = false;

    /**
    * Cache mode.
    *
    * @protected
    * @property cache
    * @type {Boolean}
    */
    protected $cache = true;

    /**
    * Clean build.
    *
    * @protected
    * @property clean
    * @type {Boolean}
    */
    protected $clean = true;

    /**
    * Release directory.
    *
    * [uncompressed, compressed]
    *
    * @protected
    * @property output_type
    * @type {String}
    */
    protected $output_type = null;

    /**
    * Paths collection.
    *
    * @protected
    * @property  paths
    * @type      {Array}
    */
    protected $paths = [];

    /**
    * URLs collection.
    *
    * @protected
    * @property  urls
    * @type      {Array}
    */
    protected $urls = [];

    /**
    * Project configuration.
    *
    * @protected
    * @property  config
    * @type      {Array}
    */
    protected $config = array
    (
        'lang'                => 'en',
        'languages'           => ['en'],
        'main_file'           => 'main.tpl',
        'toys_filename'       => 'toys.json',
        'compressor_filename' => 'compressor.php'
    );

    /**
    * All modules collection.
    *
    * @protected
    * @property  modules
    * @type      {Array}
    */
    protected $modules = [];

    /**
    * Loaded modules collection.
    *
    * @protected
    * @property  loaded_modules
    * @type      {Array}
    */
    protected $loaded_modules = [];

    /**
    * Startup modules collection..
    *
    * @protected
    * @property  startup_modules
    * @type      {Array}
    */
    protected $startup_modules = [];

    /**
    * List of all modules files indexed by type.
    *
    * @protected
    * @property  modules_files
    * @type      {Array}
    */
    protected $modules_files = [];

    /**
    * Old the rendered output.
    *
    * @protected
    * @property  output
    * @type      {String}
    */
    protected $output = '';

    /**
    * Builder class constructor.
    *
    * @constructor
    * @class  Builder
    * @param  {String} $sources_path Path to sources directory.
    * @param  {String} $release_path Path to release directory.
    * @throws {Error}
    */
    public function __construct($sources_path, $release_path)
    {
        // Set user build options
        $this->compile  = array_key_exists('compile', $_GET);
        $this->compress = array_key_exists('compress', $_GET);
        $this->cache    = ! array_key_exists('nocache', $_GET);
        $this->clean    = array_key_exists('clean', $_GET);

        // Set the output type
        $this->output_type = ($this->compress ? '' : 'un') . 'compressed';

        // Set some paths alias
        $builder_path = $this->set_path('builder', getcwd());
        $sources_path = $this->set_path('sources', $sources_path);
        $config_path  = $sources_path . '/' . $this->config['toys_filename'];
        $config_path  = $this->set_path('config' , $config_path);
        $output_path  = $this->set_path('output' , $release_path);
        $cache_path   = $this->set_path('cache'  , $builder_path, 'cache');
        $release_path = $this->set_path('release', $release_path, $this->output_type);

        // Relative linkage between the builder and the sources/release paths
        $sources_url = Helper::relative_path($builder_path, $sources_path);
        $release_url = Helper::relative_path($builder_path, $release_path);
        $this->set_url('sources', $sources_url);
        $this->set_url('release', $release_url);

        // Build base url
        $this->set_url('build', $this->compile ? '.' : $sources_url);

        // Clean release path
        if ($this->clean)
        {
            Helper::remove_path($cache_path);
            Helper::remove_path($output_path . '/compressed');
            Helper::remove_path($output_path . '/uncompressed');
        }

        // Ensure release and cache paths exists for compilation
        if ($this->compile)
        {
            Helper::make_path($cache_path);
            Helper::make_path($release_path);
        }

        // Load main configuration as array
        Helper::merge($this->config, Helper::load_json_file($config_path, true));

        // Set main file path
        $main_file = $this->get_path('sources', $this->config['main_file']);
        $this->set_path('main_file', $main_file);

        // Force main language to be the first languages provided
        $this->config['lang'] = $this->config['languages'][0];
    }

    // Magic getter for protected properties
    public function __get($key)
    {
        return $this->$key;
    }

    /**
    * Set and normalize an path alias.
    *
    * - `$path` must exist.
    * - `$subpath` is optional and can be virtual.
    *
    * @protected
    * @method set_path
    * @param  {String} $alias
    * @param  {String} $path
    * @param  {String} [...$subpath]
    * @throws {Error}  If `$path` does not exist.
    * @return {String}
    */
    protected function set_path($alias, $path, $subpath = null)
    {
        // Root path must exist an must be absolute !
        $path = Helper::absolute_path($path);

        if ($subpath)
        {
            // Concat all subpaths from arguments list
            $path .= '/' . Helper::concat_path(func_get_args(), 2);
        }

        return $this->paths[$alias] = $path;
    }

    /**
    * Return an path from alias.
    *
    * - Optionaly append some `$subpath`.
    *
    * @method get_path
    * @param  {String} $alias
    * @param  {String} [...$subpath]
    * @throws {Error}  If path alias is not defined.
    * @return {String}
    */
    public function get_path($alias, $subpath = null)
    {
        if (! isset($this->paths[$alias]))
        {
            Error::raise('Paths alias [%s] not defined.', [$alias]);
        }

        if ($subpath)
        {
            // Concat all subpaths from arguments list
            $subpath = '/' . Helper::concat_path(func_get_args(), 1);
        }

        return $this->paths[$alias] . $subpath;
    }

    /**
    * Set and normalize a url.
    *
    * @protected
    * @method set_url
    * @param  {String} $alias
    * @param  {String} $path
    * @param  {String} [...$suburl]
    * @return {String}
    */
    protected function set_url($alias, $url, $suburl = null)
    {
        $url = Helper::normalize_path($url);

        if ($suburl)
        {
            // Concat all suburls from arguments list
            $url .= '/' . Helper::concat_path(func_get_args(), 2);
        }

        return $this->urls[$alias] = $url;
    }

    /**
    * Return a url.
    *
    * @method get_url
    * @param  {String} $alias
    * @param  {String} [...$suburl]
    * @throws {Error}  If url alias is not defined.
    * @return {String}
    */
    public function get_url($alias, $suburl = null)
    {
        if (! isset($this->urls[$alias]))
        {
            Error::raise('URL alias [%s] not defined.', [$alias]);
        }

        if ($suburl)
        {
            // Concat all suburls from arguments list
            $suburl = '/' . Helper::concat_path(func_get_args(), 1);
        }

        return $this->urls[$alias] . $suburl;
    }

    /**
    * Add new module to list.
    *
    * @protected
    * @method add_module
    * @param  {String} $namespace
    * @param  {Module} $module
    */
    protected function add_module($namespace, $module)
    {
        // Current modules list
        $modules = &$this->modules;

        // For each path in namespace
        foreach (explode('/', $namespace) as $path)
        {
            // If the path is not defined (first call)
            if (! array_key_exists($path, $modules))
            {
                $modules[$path] = [];
            }

            // Set the new current list
            $modules = &$modules[$path];
        }

        // Append the module to list
        $modules[] = $module;
    }

    /**
    * Get all modules for a namespace.
    *
    * @protected
    * @method get_module
    * @param  {String} $namespace
    * @throws {Error}  If no modules found.
    * @return {Array}
    */
    protected function get_module($namespace)
    {
        // Current modules list
        $modules = &$this->modules;

        // For each path in namespace
        foreach (explode('/', $namespace) as $path)
        {
            // If the path is not defined
            if (! array_key_exists($path, $modules))
            {
                // Trow an Error exception
                Error::raise('Module "%s" not defined.', [$namespace]);
            }

            // Set the new current list
            $modules = &$modules[$path];
        }

        // Return a flattened array
        return Helper::flatten($modules);
    }

    /**
    * Return the cached file contents if found and not expired.
    *
    * @protected
    * @method cache_get
    * @param  {String} $path
    * @return {String|null}
    */
    protected function cache_get($path)
    {
        // Cache key
        $key = md5($path);

        // Cached file path
        $cached = $this->get_path('cache', $key);

        // If file exist and is not modified
        if (is_file($cached) and filemtime($cached) === filemtime($path))
        {
            // Return the cached file contents
            return file_get_contents($cached);
        }

        // Not found or expired
        return null;
    }

    /**
    * Set the file contents in cache.
    *
    * @protected
    * @method cache_set
    * @param {String} $path
    * @param {String} $data
    */
    protected function cache_set($path, $data)
    {
        // Cache key
        $key = md5($path);

        // Cached file path
        $file = $this->get_path('cache', $key);

        // Create the cached file
        file_put_contents($file, $data);

        // Sync date/time modification
        touch($file, filemtime($path));
    }

    /**
    * Scan the sources path recursively looking for modules.
    *
    * @protected
    * @method scan_modules
    * @param  {String} [$path=null]
    */
    protected function scan_modules($path = null)
    {
        // Get sources path
        $sources_path = $this->get_path('sources');

        // First call
        if (! $path)
        {
            // Set sources path to be the first path to scan
            $path = $sources_path;

            // Reset module list
            $this->modules = [];
        }

        // For each files or directory found
        foreach (glob($path . '/*') as $subpath)
        {
            // Normalize subpath
            $subpath = Helper::normalize_path($subpath);

            // Module relative path from sources path
            $relative_path = str_replace($sources_path . '/', '', $subpath);

            // Module namespace is the relative path in lowercase.
            $namespace = strtolower($relative_path);

            // If it look like a valid module
            if (is_file($subpath . '/' . $this->config['toys_filename']))
            {
                // Create and add the new module to modules list
                $this->add_module($namespace, new Module($this, $namespace));
            }

            // If subdirectory
            if (is_dir($subpath))
            {
                // Scan module path looking for submodules
                $this->scan_modules($subpath, $sources_path);
            }
        }
    }

    /**
    * Try to load a module and all of his dependencies.
    *
    * @protected
    * @method load_module
    * @param  {String} $namespace
    * @throws {Error}  If module is not defined.
    */
    protected function load_module($namespace)
    {
        // Get module(s)
        $modules = $this->get_module($namespace);

        // For each module in namespaces
        foreach ($modules as $module)
        {
            // If the module is allready loaded
            if (isset($this->loaded_modules[$module->namespace]))
            {
                // Skip this module.
                continue;
            }

            // Add module to loaded modules list
            $this->loaded_modules[$module->namespace] = $module->namespace;

            // Load module dependencies
            if (! empty($module->config['require']))
            {
                foreach ($module->config['require'] as $require)
                {
                    $this->load_module($require);
                }
            }

            // Autoload module at startup ?
            if ($module->config['autoload'])
            {
                $this->startup_modules[] = $module->class_name;
            }

            // Add all files to global list
            foreach ($module->files as $type => $files)
            {
                // Do not populate 'models', since is prepended to 'scripts'
                if ($type == 'models')
                {
                    continue;
                }

                // Not already defined type
                if (! isset($this->modules_files[$type]))
                {
                    $this->modules_files[$type] = [];
                }

                // Prepends models to scripts list
                if ($type == 'scripts')
                {
                    $files = array_merge($module->files['models'], $files);
                }

                // Merge with existing files list
                Helper::merge($this->modules_files[$type], $files);
            }
        }
    }

    /**
    * Load all modules with autoload set to true.
    *
    * @protected
    * @method load_modules
    */
    protected function load_modules()
    {
        // Reset modules lists
        $this->loaded_modules  = [];
        $this->startup_modules = [];
        $this->modules_files   = [];

        // Set default files types
        foreach (Module::$autoload as $type)
        {
            $this->modules_files[$type] = [];
        }

        // Get flatten modules list
        $modules = Helper::flatten($this->modules);

        // For each modules found
        foreach ($modules as $module)
        {
            // If autoload set to true
            if ($module->config['autoload'])
            {
                $this->load_module($module->namespace);
            }
        }
    }

    /**
    * Render an HTML collection as string.
    *
    * @protected
    * @method render_html_collection
    * @param  {String}  $type
    * @param  {Array}   $collection
    * @param  {Integer} [$tabs=2]
    */
    protected function render_html_collection($type, $collection, $tabs = 2)
    {
        // Block label
        $label = ucfirst($type);

        // Comment label
        $buffer = "<!-- $label -->\n";

        // For each line
        foreach ($collection as $line)
        {
            $buffer .= str_repeat("\t", $tabs) . "$line\n";
        }

        // Return the buffer
        return trim($buffer);
    }

    /**
    * Render assets by type.
    *
    * @protected
    * @method render_assets
    * @param  {String} $type
    * @return {String}
    */
    protected function render_assets($type)
    {
        // HTML tag collection
        $html_collection = array();

        // Assets collection
        $assets = $this->modules_files[$type];

        // No assets to render
        if (empty($assets))
        {
            return '';
        }

        // If compress drirective = true
        if ($this->compress)
        {
            // Append asset start tag
            if ($type == 'styles')
            {
                $html_collection[] = '<style type="text/css">';
            }
            else
            {
                $html_collection[] = '<script type="text/javascript">';
            }
        }

        // For each asset
        foreach ($assets as $asset)
        {
            // If compress mode
            if ($this->compress)
            {
                // Create the asset html contents
                $html = '/* ===>>> ' . $asset->url . ' */' . $asset->data;

                // Append asset content
                $html_collection[] = $html;
            }
            else
            {
                // Append asset tag
                if ($type == 'styles')
                {
                    $tag = '<link href="' . $asset->url . '" rel="stylesheet">';
                }
                else
                {
                    $tag = '<script src="' . $asset->url . '"></script>';
                }

                $html_collection[] = $tag;
            }
        }

        // If compress drirective = true
        if ($this->compress)
        {
            // Append asset start tag
            if ($type == 'styles')
            {
                $html_collection[] = '</style>';
            }
            else
            {
                $html_collection[] = '</script>';
            }
        }

        // Return assets block
        return $this->render_html_collection($type, $html_collection);
    }

    /**
    * Stylesheets render.
    *
    * @protected
    * @method render_styles_tag
    * @return {String}
    */
    protected function render_styles_tag()
    {
        return $this->render_assets('styles');
    }

    /**
    * Scripts render.
    *
    * @protected
    * @method render_scripts_tag
    * @return {String}
    */
    protected function render_scripts_tag()
    {
        return $this->render_assets('scripts');
    }

    /**
    * Views render.
    *
    * @protected
    * @method render_views_tag
    * @return {String}
    */
    protected function render_views_tag()
    {
        // No views to render
        if (empty($this->modules_files['views']))
        {
            return '';
        }

        // HTML tag collection
        $html_collection = array();

        // for each views
        foreach ($this->modules_files['views'] as $view)
        {
            // Set the view name (filename without extenssion)
            $name = pathinfo($view->name, PATHINFO_FILENAME);

            // Set the view DOM id (kebab-case)
            $id = preg_replace('|[^a-zA-Z0-9_\-]+|', '-', $name);
            $id = strtolower($view->module->id . '-' . $id) . '-view';

            // Append HTML start tag
            $html_collection[] = '<script type="text/html" id="' . $id . '">';

            // Append view content
            $html_collection[] = Helper::get_file_contents($view->path);

            // Append HTML end tag
            $html_collection[] = '</script>';
        }

        // Return assets block
        return $this->render_html_collection('views', $html_collection);
    }

    /**
    * Return config as JSON string.
    *
    * @protected
    * @method render_config_tag
    * @return {String}
    */
    protected function render_config_tag()
    {
        return json_encode($this->config);
    }

    /**
    * Texts render.
    *
    * @protected
    * @method render_texts_tag
    * @return {String}
    */
    protected function render_texts_tag()
    {
        // HTML tag collection
        $html_collection = array();

        // Compiled collection
        $compiled = array();

        // For each language file
        foreach ($this->modules_files['lang'] as $file)
        {
            // Language is the filename without extenssion
            $lang = pathinfo($file->name, PATHINFO_FILENAME);

            // Load the language file as array
            $texts = Helper::load_json_file($file->path, true);

            // If language collection not defined
            if (! isset($compiled[$lang]))
            {
                $compiled[$lang] = array();
            }

            // For each text
            foreach ($texts as $key => $text)
            {
                // Module index
                $index = $file->module->class_name;

                // If module not defined
                if (! isset($compiled[$lang][$index]))
                {
                    $compiled[$lang][$index] = array();
                }

                // Prefixed key
                $compiled[$lang][$index][$key] = $text;
            }
        }

        // Return JSON string
        return json_encode($compiled);
    }

    /**
    * Return startup modules list as JSON string.
    *
    * @protected
    * @method render_modules_tag
    * @return {String}
    */
    protected function render_modules_tag()
    {
        return json_encode(array_values($this->startup_modules));
    }

    /**
    * Simple view parser for the main file.
    *
    * @protected
    * @method parse_main_file
    * @param  {Array} $matches
    * @return {String}
    */
    protected function parse_main_file($matches)
    {
        // Extract the key
        $key = trim($matches[1]);

        // If config variable exist
        if (array_key_exists($key, $this->config))
        {
            // Return configuration item as string
            return (string) $this->config[$key];
        }

        // Extract callback name
        $method = 'render_' . $key . '_tag';

        // If callback exist
        if (method_exists($this, $method))
        {
            // Return the callback result
            return $this->$method();
        }

        // If local variable exist
        if (property_exists($this, $key))
        {
            // Return local property as string
            return (string) $this->$key;
        }

        // Return original
        return $matches[0];
    }

    /**
    * Render the output buffer.
    *
    * @protected
    * @method render_output
    */
    protected function render_output()
    {
        // Empty buffer
        $this->output = '';

        // Start buffer
        ob_start();

        // Include the view
        require $this->get_path('main_file');

        // Get and clean the buffer
        $this->output = ob_get_clean();

        // Normalize the buffer
        $this->output = Helper::normalize_contents($this->output);

        // Parse the buffer tags
        $this->output = preg_replace_callback
        (
            '|{{([^}}]+?)}}|u', array($this,'parse_main_file'), $this->output
        );

        // Return the buffer
        return $this->output;
    }

    /**
    * Copy a module file (excluding views an languages files).
    *
    * @protected
    * @method copy_file
    * @param  {File} $file
    */
    protected function copy_file($file)
    {
        // Never copy models, views or languages files
        if (! in_array($file->type, array('views', 'lang')))
        {
            Helper::copy($file->path, $file->release_path);
        }
    }

    /**
    * Call user compressor.
    *
    * @protected
    * @method call_user_compressor
    * @param  {File} $file
    * @return {Boolean}
    */
    protected function call_user_compressor($file)
    {
        // File type
        $type = $file->type;

        // File module instance
        $module = $file->module;

        // Compressor instance
        $compressor = $module->compressor;

        // No compressor for this file
        if (! $compressor or ! isset($compressor->$type))
        {
            // Return default action value
            return null;
        }

        // Call the compressor callback with file at first param
        $result = call_user_func($compressor->$type, $file);

        // Errors handler
        if (is_string($result))
        {
            Error::raise($result);
        }
        else if (is_array($result))
        {
            Error::raise($result[0], $result[1]);
        }

        // Return the result
        return $result;
    }

    /**
    * Compress a module file.
    *
    * @protected
    * @method compress_file
    * @param  {File} $file
    */
    protected function compress_file($file)
    {
        if (! $file->compressible)
        {
            // Call user compressor callback
            $result = $this->call_user_compressor($file);

            // Copy file on null or true return value
            if ($result === true or $result === null)
            {
                $this->copy_file($file);
            }
        }
        else
        {
            // Cache enabled ?
            if ($this->cache)
            {
                // Try to get the file from cache
                $data = $this->cache_get($file->path);

                if ($data)
                {
                    // Set file contents
                    $file->data = $data;

                    // And exit.
                    return;
                }
            }

            // Set file contents
            $file->get_data();

            // Call user compressor callback
            $result = $this->call_user_compressor($file);

            // Minification on null or true return value
            if ($result === true or $result === null)
            {
                if ($file->type === 'styles')
                {
                    $file->data = Helper::minify_css($file->data);
                }
                else if ($file->type === 'scripts' or $file->type === 'models')
                {
                    $file->data = Helper::minify_js($file->data);
                }
                else if ($file->type === 'views')
                {
                    $file->data = Helper::minify_html($file->data);
                }
            }

            if ($this->cache)
            {
                // Update/Set the cached file contents
                $this->cache_set($file->path, $file->data);
            }
        }
    }

    /**
    * Compile (and compress) all modules.
    *
    * - Do nothing if not in compilation mode.
    *
    * @protected
    * @method compile_modules
    */
    protected function compile_modules()
    {
        // Not in compilation mode, exit..
        if (! $this->compile)
        {
            return;
        }

        // Define callback
        $callback = $this->compress ? 'compress_file' : 'copy_file';

        // For each files
        foreach ($this->modules_files as $files)
        {
            // For each module file type
            foreach ($files as $file)
            {
                $this->$callback($file);
            }
        }
    }

    /**
    * Write output buffer to the main file in release directory
    * and override the output with an link to the compilded project.
    *
    * @protected
    * @method write_main_file
    */
    protected function write_main_file()
    {
        // Not in compilation mode, exit..
        if (! $this->compile)
        {
            return;
        }

        // Output path
        $release_path = $this->get_path('release');

        // Ensure the destination path exist
        Helper::make_path($release_path);

        // Create the index file from output buffer
        file_put_contents($release_path . '/index.html', $this->output);

        // Get the (virtual) linkage between
        // the builder and the release directoties
        $release_url = $this->get_url('release');

        // Update the output buffer
        $type  = ucfirst($this->output_type);
        $host  = Helper::get_base_url();
        $link  = $host . dirname($_SERVER['SCRIPT_NAME']) . '/' . $release_url;
        $title = "Toys builder - $type version";

        $this->output  = "<html lang=\"en\"><head><meta charset=\"utf-8\">";
        $this->output .= "<title>$title</title></head><body>";
        $this->output .= "<h1>$type version</h1><hr /><pre>";
        $this->output .= "<b>Live location :</b> <a href=\"$link\">$link</a>\n";
        $this->output .= "<b>File location :</b> $release_path";
        $this->output .= "</pre></body></html>";
    }

    /**
    * Simple module scaffolding.
    *
    * @todo Make a dedicated class !
    * @protected
    * @method make_module
    */
    protected function make_module()
    {
        // No module name provided
        if (empty($_GET['make']))
        {
            // Exit...
            return;
        }

        // Set and normalize the provided namespace to create
        $namespace = Helper::normalize_path(trim(strtolower($_GET['make'])));

        // Try to get the module
        try
        {
            $module = $this->get_module($namespace);
        }
        catch(\Exception $e) {}

        // Module alrady exist
        if (! empty($module))
        {
            Error::raise('Module [%s] already exists.', [$namespace]);
        }

        // Set the main module class name
        $class_name = Helper::classify($namespace);

        // Set the base path
        $path = $this->get_path('sources', $namespace);

        //var_dump([$namespace, $class_name, $path]);

        // Make base directories
        foreach (Module::$autoload as $directory)
        {
            Helper::make_path($path . '/' . $directory);
            //var_dump($path . '/' . $directory);
        }

        // Make the toys.json file
        $data = "{\n";
        foreach (Module::$defaults as $key => $value)
        {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            $pad   = str_pad(' ', 9 - strlen($key));
            $data .= "\t\"$key\"$pad: $value,\n";
        }
        $data = trim($data, "\n,") . "\n}\n";

        $file = $path . '/' . $this->config['toys_filename'];
        file_put_contents($file, $data);
        //var_dump([$file, $data]);

        // Make the main language file
        $data = "{\n\t\"name\" : \"$class_name\"\n}\n";
        $file = $path . '/lang/' . $this->config['lang'] . '.json';
        file_put_contents($file, $data);
        //var_dump([$file, $data]);

        // Make the main module file
        $data = "var ${class_name} = ToysModule.extend(\n{\n\t\n});\n";
        $file = $path . '/module.js';
        file_put_contents($file, $data);
        //var_dump([$file, $data]);

        // Make a default model file
        $data = "var ${class_name}Model = ToysModel.extend(\n{\n\t\n});\n";
        $file = $path . '/models/model.js';
        file_put_contents($file, $data);
        //var_dump([$file, $data]);

        /*
        // Make a default helper script file
        $data = "var ${class_name}Helper =\n{\n\t\n};\n";
        $file = $path . '/scripts/helper.js';
        file_put_contents($file, $data);
        //var_dump([$file, $data]);
        */

        // Make a default style file
        $id   = strtolower($class_name);
        $data = "#$id\n{\n\t\n}\n";
        $file = $path . '/styles/default.css';
        file_put_contents($file, $data);
        //var_dump([$file, $data]);

        // Make a default view file
        $file = $path . '/views/default.tpl';
        $data  = "<div id=\"$id\">\n";
        $data .= "\t<h1>$class_name</h1>\n";
        $data .= "\t<p>Default view at $file.</p>\n";
        $data .= "</div>\n";
        file_put_contents($file, $data);
        //var_dump([$file, $data]);
    }

    /**
    * Build the whole project.
    *
    * @method build
    * @chainable
    */
    public function build()
    {
        // Looking for all installed modules
        $this->scan_modules();

        // Module scaffolding
        $this->make_module();

        // Load all modules with autoload set to true
        $this->load_modules();

        // Compile all loaded modules
        $this->compile_modules();

        // Render output
        $this->render_output();

        // Write the main file
        $this->write_main_file();

        // Chaining
        return $this;
    }

    /**
    * Render the build output.
    *
    * @method render
    * @return String
    */
    public function render()
    {
        return $this->output;
    }

    /**
    * Render and display the build output.
    *
    * @method display
    */
    public function display()
    {
        echo $this->render();
    }
}
