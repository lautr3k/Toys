<?php
/**
 * Toys - Project builder.
 *
 * @license   GPL
 * @version   1.0.0
 * @copyright 2015 Onl'Fait (http://www.onlfait.ch)
 * @author    Sébastien Mischler (skarab) <sebastien@onlfait.ch>
 * @link      https://github.com/lautr3k/Toys
 */
require __DIR__.'/autoload.php';

try
{
    // Create the project builder instance
    $builder = new \Toys\Builder('../src', '../dist');

    // Build and display the output
    $builder->build()->display();
}
catch (\Exception $e)
{
    $message = $e->getMessage();
    $file    = $e->getFile();
    $line    = $e->getLine();

    $output  = "<html lang=\"en\"><head><meta charset=\"utf-8\">";
    $output .= "<title>Error !</title></head><body>";
    $output .= "<h1>Error !</h1><hr /><pre>";
    $output .= "<b>Message :</b> $message\n";
    $output .= "<b>File    :</b> $file\n";
    $output .= "<b>Line    :</b> $line\n";
    $output .= "</pre></body></html>";

    echo $output;
}
