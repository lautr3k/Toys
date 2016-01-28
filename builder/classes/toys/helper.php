<?php namespace Toys;
/**
* Collection of static convenience methods.
*
* @license   GPL
* @version   1.0.0
* @copyright 2015 Onl'Fait (http://www.onlfait.ch)
* @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
* @link      https://github.com/lautr3k/Toys
* @module    Toys
* @class     Helper
*/
class Helper
{
    /**
    * Return a path where all path separators was replaced by a url separator,
    * and the starting and trailing path separator removed.
    *
    * @static
    * @method normalize_path
    * @param  {String} $path
    * @return {String}
    */
    public static function normalize_path($path)
    {
        return trim(preg_replace('|[\\\\/]+|', '/', $path), '/');
    }

    /**
    * Return a normalized and absolute path if path exist
    * or the normalized provided path if `$strict = false`
    * otherwise an error is raised.
    *
    * @static
    * @method absolute_path
    * @param  {String}  $path
    * @param  {Boolean} [$strict=true] Throw an exception if does not exist.
    * @throws {Error} If `$path` does not exist and `$strict = true`.
    * @return {String}
    */
    public static function absolute_path($path, $strict = true)
    {
        // If the path exist
        if (file_exists($path))
        {
            // Get the absolute path
            $path = realpath($path);
        }

        // If the path does not exist and strict mode
        else
        {
            if ($strict)
            {
                Error::raise('Path "%s" does not exist.', [$path]);
            }
        }

        // Return a normalized path
        return self::normalize_path($path);
    }

    /**
    * Return the relative path from one path to another.
    *
    * @static
    * @method relative_path
    * @link   http://php.net/manual/fr/function.realpath.php#105876
    * @param  {String} $from
    * @param  {String} $to
    * @param  {String} [$ps='/']
    * @return {String}
    */
    public static function relative_path($from, $to, $ps = '/')
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo   = explode($ps, rtrim($to, $ps));

        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
        {
            array_shift($arFrom);
            array_shift($arTo);
        }

        return str_pad('', count($arFrom) * 3, '..'.$ps) . implode($ps, $arTo);
    }

    /**
    * Return a path from array of subpaths.
    *
    * @static
    * @method concat_path
    * @param  {String}  $paths
    * @param  {Integer} [$offset=0]
    * @param  {Integer} [$length=null]
    * @return {String}
    */
    public static function concat_path($paths, $offset = 0, $length = null)
    {
        return trim(implode('/', array_slice($paths, $offset, $length)), '/');
    }

    /**
    * Normalize input content.
    *
    * - CRLF normalization.
    * - Trim witespaces.
    * - Tabs to spaces.
    *
    * @static
    * @method normalize_contents
    * @param  {String} $content
    * @return {String}
    */
    public static function normalize_contents($content)
    {
        // Normalize CRLF to UNIX style
        $content = str_replace("\r\n", "\n", $content);

        // Normalize TABS with four spaces
        $content = str_replace("\t", "    ", $content);

        // Trim witespaces
        $content = trim($content);

        // Return the file content
        return $content;
    }

    /**
    * File get content with some addition.
    *
    * - CRLF normalization.
    * - Trim witespaces.
    *
    * @static
    * @method get_file_contents
    * @param  {String} $file
    * @return {String}
    */
    public static function get_file_contents($file)
    {
        // Get the file content
        $file_content = file_get_contents($file);

        // Return the normalized file content
        return self::normalize_contents($file_content);
    }

    /**
    * JSON errors messages.
    *
    * @static
    * @property JSON_ERROR_MESSAGES
    * @type {Array}
    */
    public static $JSON_ERROR_MESSAGES = array
    (
        JSON_ERROR_NONE           => 'No compressor found (empty file ?).',
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded.',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch.',
        JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found.',
        JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON.',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters.'
    );

    /**
    * Load and decode and return the result of a json file.
    *
    * Throw an exception on JSON decode error.
    *
    * @static
    * @method load_json_file
    * @param  {String}  $file
    * @param  {Boolean} [$assoc=false]
    * @throws {Error} On parse error.
    * @return {stdClass|String}
    */
    public static function load_json_file($file, $assoc = false)
    {
        // Get the JSON file content
        $file_content = self::get_file_contents($file);

        // Decode JSON content as object
        $compressor = json_decode($file_content, $assoc);

        // Test JSON format
        if (! $compressor)
        {
            // Get error code
            $error_code = json_last_error();

            // Error message
            $error_message = 'Unknown error';

            // Known error code
            if (isset(self::$JSON_ERROR_MESSAGES[$error_code]))
            {
                $error_message = self::$JSON_ERROR_MESSAGES[$error_code];
            }

            // Throw an exception
            throw new Error('%s for decoding : %s', [$error_message, $file]);
        }

        // Return the compressor
        return $compressor;
    }

    /**
    * Return an flatten array.
    *
    * @static
    * @method flatten
    * @param  {Array} $input
    * @return {Array}
    */
    public static function flatten($input)
    {
        $output = array();

        array_walk_recursive($input, function ($current) use (&$output)
        {
            $output[] = $current;
        });

        return $output;
    }

    /**
    * Set/force the property to be an array.
    *
    * @static
    * @method merge
    * @param  {Array} &$arr1
    * @param  {Array} $arr2
    */
    public static function merge(&$arr1, $arr2)
    {
        $arr1 = array_merge($arr1, $arr2);
    }

    /**
    * Set/force the property to be an array.
    *
    * @static
    * @method force_array
    * @param  {Class}  &$target
    * @param  {String} $index
    */
    public static function force_array(&$target, $index)
    {
        if (is_object($target))
        {
            // If the target is not set
            if (! isset($target->$index))
            {
                $target->$index = array();
            }

            // Force array type
            return $target->$index = (array) $target->$index;
        }
        else
        {
            // If the target is not set
            if (! isset($target[$index]))
            {
                $target[$index] = array();
            }

            // Force array type
            return $target[$index] = (array) $target[$index];
        }
    }

    /**
    * Return a flat files three (recursive).
    *
    * @static
    * @method scan_path
    * @param  {String} $path
    * @return {String}
    */
    public static function scan_path($path)
    {
        // Files list
        $paths = array();

        // For each file
        foreach (scandir($path) as $file)
        {
            // Skip parent and current directories
            if (in_array($file, array('.', '..')))
            {
                continue;
            }

            // Current path
            $current_path = $path.'/'.$file;

            // Add path to paths list
            $paths[] = $current_path;

            // If it is a directory
            if (is_dir($current_path))
            {
                // Recursive scan
                $paths = array_merge($paths, self::scan_path($current_path));
            }
        }

        return $paths;
    }

    /**
    * Make a path recursively.
    *
    * @static
    * @method make_path
    * @param  {String} $path
    * @return void
    */
    public static function make_path($path)
    {
        // Split source path on separator
        $path = explode('/', $path);

        // Current path
        $current_path = '';

        // For each path part
        foreach ($path as $path_part)
        {
            // Increment current paths
            if ($current_path)
            {
                $current_path .= '/'.$path_part;
            }
            else
            {
                // First call path
                $current_path = $path_part;
            }

            // If not a directory
            if (! is_dir($current_path))
            {
                // Make the directory
                mkdir($current_path);
            }
        }
    }

    /**
    * Remove a path recursively.
    *
    * @static
    * @method remove_path
    * @param  {String} $path
    * @return bool
    */
    public static function remove_path($path)
    {
        // File not found
        if (! file_exists($path))
        {
            return false;
        }

        // Remove file or symlink
        if (! is_dir($path) or is_link($path))
        {
            return unlink($path);
        }

        // For each file
        foreach (scandir($path) as $file)
        {
            // Not parent or current directoriy
            if (! in_array($file, array('.', '..')))
            {
                self::remove_path($path . '/' . $file);
            }
        }

        // Remove empty directory
        return rmdir($path);
    }

    /**
    * Copy a file even if the path does not exist.
    *
    * @static
    * @method copy
    * @param  {String} $source
    * @param  {String} $destination
    */
    public static function copy($source, $destination)
    {
        // Source and destination path
        $source_path      = $source;
        $destination_path = $destination;

        // Source type
        $is_file = is_file($source_path);

        // If the source is a file
        if ($is_file)
        {
            // Strip file name
            $source_path      = dirname($source_path);
            $destination_path = dirname($destination_path);
        }

        // Ensure the destination path exist
        self::make_path($destination_path);

        // Copy the file
        if ($is_file)
        {
            copy($source, $destination);
        }
    }

    /**
    * Test if the current connection use SSL.
    *
    * @static
    * @method is_ssl
    * @return {Boolean}
    */
    public static function is_ssl()
    {
        return (! empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off')
                or $_SERVER['SERVER_PORT'] == 443;
    }

    /**
    * Return the server protocol.
    *
    * @static
    * @method get_server_protocol
    * @return {String}
    */
    public static function get_server_protocol()
    {
        $protocol = strtolower($_SERVER['SERVER_PROTOCOL']);
        return substr($protocol, 0, strpos($protocol, '/' ));
    }

    /**
    * Return the server host name.
    *
    * @static
    * @method get_server_host
    * @param  {Boolean} [$deep=true] Use forwarded host if exist.
    * @return {String}
    */
    public static function get_server_host($deep = true)
    {
        if ($deep and isset($_SERVER['HTTP_X_FORWARDED_HOST']))
        {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        else if (isset($_SERVER['HTTP_HOST']))
        {
            $host = $_SERVER['HTTP_HOST'];
        }
        else
        {
            $host = $_SERVER['SERVER_NAME'];
        }

        // Return the host without port number
        return substr($host, strpos($host, ':'));
    }

    /**
    * Return the current page base url.
    *
    * @static
    * @method get_base_url
    * @param  {Boolean} [$deep=true] Use forwarded host if exist.
    * @return void
    */
    public static function get_base_url($deep = true)
    {
        // Is an SSL connection
        $is_ssl = self::is_ssl();

        // URL protocol
        $protocol = self::get_server_protocol() . ($is_ssl ? 's' : '');

        // Base URL
        $url = $protocol . '://' . self::get_server_host($deep);

        // Port
        $port = $_SERVER['SERVER_PORT'];

        // Append port number if not an default value
        if ((! $is_ssl && $port != '80') or ($is_ssl and $port != '443'))
        {
            $url.= ':' . $port;
        }

        return $url;
    }

    /**
    * Remove the first comment block...
    *
    * @static
    * @method remove_first_comment_block
    * @param  {String} $data
    * @return {String}
    */
    public static function remove_first_comment_block($data)
    {
        while (strpos($data, '/*') === 0)
        {
            $data = trim(substr($data, strpos($data, '*/') + 2));
        }

        return $data;
    }

    /**
    * HTML Minifier. (Not implemented!)
    *
    * @static
    * @method minify_html
    * @param  {String} $data
    * @return {String}
    */
    public static function minify_html($data)
    {
        return $data;
    }

    /**
    * CSS Minifier.
    *
    * @static
    * @method minify_css
    * @param  {String} $data
    * @return {String}
    */
    public static function minify_css($data)
    {
        // Static compressor
        static $compressor = null;

        // If not defined
        if (! $compressor)
        {
            // Create new CSS minifier instance
            $compressor = new \CSSmin\CSSmin();
        }

        // Set the new data
        $data = $compressor->run($data);

        // Force removing first block of comments
        return self::remove_first_comment_block($data);
    }

    /**
    * JS Minifier.
    *
    * @static
    * @method minify_js
    * @param  {String} $data
    * @return {String}
    */
    public static function minify_js($data)
    {
        // Run the minifier
        $data = \JSMin\JSMin::minify($data);

        // Replace all new line chars
        $data = str_replace("\n", ' ', $data);

        // Force removing first block of comments
        return self::remove_first_comment_block($data);
    }

    /**
    * Return a ClassName string.
    *
    * @static
    * @method classify
    * @param  {String} $input
    * @return {String}
    */
    public static function classify($input)
    {
        $input = preg_split('/[^a-z0-9]+/i', $input);
        $input = array_map('ucfirst', $input);
        return implode('', $input);
    }
}
