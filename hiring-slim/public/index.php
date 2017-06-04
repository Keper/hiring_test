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


// $app->get('/import', function (Request $request, Response $response) {
//   var_dump('ciao');
  
//   $response->getBody()->write(var_export($tickets, true));
//   return $response;
// });


// $db = AutoDb::init($container->db);

// $sql = "SELECT * FROM category";
// $data = $db->rowsArray('category', 'id_category = 10',null);

// $table = $db->newRow('category');
// $table->attr('desc_category','Prova2');
// $table->attr('flg_xxx',1);
// $table->attr('desc_category','Prova3');
// $table->attr('flg_xxx',0);
// $table->save();

// $table->saveMore($arrayOfAutoRecords);

// var_dump($data[0]->attr('id_category'),$data);
// var_dump($table);
/*
$stmt = $container->db->prepare($sql);
$stmt->execute();
$stmt->bind_result($id_category,$desc_category,$flg_xxx);

while($stmt->fetch()) {
  var_dump($id_category,$desc_category,$flg_xxx);
}
*/