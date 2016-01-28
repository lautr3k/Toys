<?php namespace Toys;
/**
 * Exception wrapper with message formatting option.
 *
 * @license   GPL
 * @version   1.0.0
 * @copyright 2015 Onl'Fait (http://www.onlfait.ch)
 * @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
 * @link      https://github.com/lautr3k/Toys
 * @module    Toys
 */
class Error extends \ErrorException
{
    /**
    * Exception wrapper with message formatting option.
    *
    *  **Usage:**
    *
    *      // Without arguments.
    *      throw new Error('Unknown error: Empty string.');
    *
    *      // With arguments.
    *      throw new Error('%s error: %s', ['Unknown', 'Empty string.']);
    *
    * @constructor
    * @class Error
    * @param {String} $message
    * @param {Array}  [$args]
    */
    public function __construct($message, $args = array())
    {
        // If arguments provided
        if (! empty($args))
        {
            // Format the message
            $message = vsprintf($message, $args);
        }

        // Set message
        $this->message = $message;
    }

    /**
    * Convenient static method to throw an error exception.
    *
    *  **Usage:**
    *
    *      // Without arguments.
    *      Error::raise('Unknown error: Empty string.');
    *
    *      // With arguments.
    *      Error::raise('%s error: %s', ['Unknown', 'Empty string.']);
    *
    * @static
    * @method raise
    * @param  {String} $message
    * @param  {Array}  [$args]
    * @throws {Error}
    */
    public static function raise($message, $args = array())
    {
        throw new self($message, $args);
    }
}
