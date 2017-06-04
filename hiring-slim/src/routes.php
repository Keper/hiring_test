<?php
// Routes
use AutoDb\AutoDb;

$app->get('/import', function ($request, $response, $args) {
  
  $db = AutoDb::init($this->db);
  
  require_once 'load_data.php';

  $response = $this->renderer->render($response, "import.phtml", ["res" => $res, "num_cat" => $num_cat, "num_auth"=>$num_auth, "num_books"=>$num_books]);
  
  return $response;
});
  
$app->get('/table', function ($request, $response, $args) {

    $book = new books($this->db);
    $books = $book->getTableData();
    $filter = $book->labelFlgAdult();
    
    $paginator_start = $book->paginator();
    $paginator_end = $book->paginator($book->numPage()-4);
    
    $_SESSION['page'] = 1;
    $response = $this->renderer->render($response, "table.phtml", ['books'=>$books, 'filter'=>$filter, 'paginator_start'=>$paginator_start, 'paginator_end'=>$paginator_end]);
    
    return $response;
});

$app->any('/paginator', function ($request, $response, $args) {
  
  $data = $request->getParsedBody();
  $link = $data['link'];
  
  $book = new books($this->db);
  $book->logger = $this->logger;
  $html['paginator'] = $book->changePaginator($link);
  $html['table'] = $book->changeTable();
  
  echo json_encode($html);
  
});

$app->any('/search', function ($request, $response, $args) {
  
  $data = $request->getParsedBody();
  $search = $data['search'];
  
  $book = new books($this->db);
  $book->logger = $this->logger;
  $books = $book->search($search);
  
  $response = $this->renderer->render($response, "table.phtml", ['books'=>$books]);
  
  return $response;
  
});

$app->get('/adult', function ($request, $response, $args) {
  
  $book = new books($this->db);
  $book->logger = $this->logger;
  $book->toggleFlgAdult();
  $filter = $book->labelFlgAdult();
  
  $books = $book->getTableData();
  
  $_SESSION['page'] = 1;
  $response = $this->renderer->render($response, "table.phtml", ['books'=>$books, 'filter'=>$filter]);
  
  return $response;
  
});

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
//     $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
