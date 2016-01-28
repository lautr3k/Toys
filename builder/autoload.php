<?php spl_autoload_register(function($class_name) {
    require __DIR__ . '/classes/' . strtolower($class_name) . '.php';
});
