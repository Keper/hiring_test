<?php

use loader\SplClassLoader;
/*
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
*/
require_once $_SERVER['DOCUMENT_ROOT'].'/../classes/loader/SplClassLoader.php';
$loader = new SplClassLoader( null, $_SERVER['DOCUMENT_ROOT'].'/../classes');
$loader->register();

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';
  
// $container->logger->addInfo("Something interesting happened");
// Run app
$app->run();
