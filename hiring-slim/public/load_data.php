<?php
$files = array();
$files['categories'] = $_SERVER['DOCUMENT_ROOT']."/../repository/categories.csv";
$files['library'] = $_SERVER['DOCUMENT_ROOT']."/../repository/libreria.csv";
$field_names = array();
$res = false;
$num_cat = 0;
$num_auth = 0;
$num_books = 0;

try{
  
  foreach($files as $name=>$filename) {
    
    if(is_readable($filename)) {
      
      //First at all loading the categories to randomly assign it to the books in a second moment
      $fh = fopen($filename, 'r');
      
      $row_count = 0;
      $this->logger->addDebug('Inizio '.$filename);
      
      while(!feof($fh)) {
        
        $tmp = 	fgetcsv($fh, 0, '|');
        
        if(is_array($tmp)) {
          
          // Loading into the array only the data
          if(count($field_names) > 0) {
            
            // Mi porto dietro solo i record con lo stesso numero di campi dell'intestazione
            if(count($tmp) == count($field_names))
              $rows[] = $tmp;
            else {
              $res = false;
              Throw new Exception ('Row #'.($row_count+1).' contain a wrong number of fields: '.count($tmp).' fields '.count($field_names).' needed - '.print_r($tmp,true));
            }
          }
          else {
            $rows = $tmp;
          }
          
          if($row_count == 0) {
            
            $field_names = array_flip($rows);
            unset($rows);
          }
          
        }
        ++$row_count;
        
      }
      $this->logger->addDebug('Ha finito '.$filename);
      
      if($name == 'categories') {
        //Saving categories
        
        
        foreach($rows as $category) {
          
          if(strtolower($category[$field_names['Adult']]) == 'yes')
            $flg_xxx = 1;
          else
            $flg_xxx = 0;
          
          $table = $db->newRow('category');
          $table->attr('desc_category',$this->db->real_escape_string($category[$field_names['Category']]));
          $table->attr('flg_xxx',$this->db->real_escape_string($flg_xxx));
          
          $table->save();
          ++$num_cat;
        }
        
      }
      else {
        //Saving author and then the book
        if($name == 'library') { 
              
          // Reading the rows loaded
          foreach($rows as $library_rec) {
            
            // Reading author data
            if(isset($library_rec[$field_names['Autore']])) {
              
              $authname = trim($this->db->real_escape_string($library_rec[$field_names['Autore']]));
              
              $table = $db->newRow('authors');
              $sql = "SELECT id_author FROM authors WHERE authorname ='".$authname."';";
              
              $res = $this->db->query($sql);
              
              // If author doesn't exist adding it
              if($res->num_rows == 0) { 
                
                $table->attr('authorname',$authname);
                $table->save();

                ++$num_auth;
                $id_auth = $this->db->insert_id;
                
              }
            }
          }
          
          foreach ($rows as $library_rec) {
            
            // Adding books, to assign category (only one) and more than one author select them randomly from table
            $table_book = $db->newRow('books');
            
            $title = trim($this->db->real_escape_string($library_rec[$field_names['Titolo']]));
            
            $sql = "SELECT id_category FROM category ORDER BY rand() LIMIT 1";
            $res = $this->db->query($sql);
            $id_category= $res->fetch_assoc()['id_category'];
            
            $table_book->attr('title',$title);
            $table_book->attr('id_category',$id_category);
            $table_book->save();
            
            $id_book = $this->db->insert_id;
            $this->logger->addDebug('id_book: '.$id_book);
            ++$num_books;
            
            // Get author min. 1 max. 4
            $num_authors = (int)rand(1,4);
            $this->logger->addDebug('id_authors: '.$num_authors);
            
            $id_auths = array();
            for($i = 1; $i <= $num_authors; $i++ ){
              
              $sql = "SELECT id_author FROM authors ORDER BY rand() LIMIT 1";
              $res = $this->db->query($sql);

              $id = $res->fetch_assoc()['id_author'];
              if(!in_array($id, $id_auths))
                $id_auths[] = $id;
                
            }
            $this->logger->addDebug('codici_autori: '.print_r($id_auths, true));
            
            foreach($id_auths as $id_auth) {
              
              // I need to use mysqli beacuse AutoDb doesn't support table with primary key with a primary key composed
              $sql = "INSERT INTO books_authors VALUES(".$id_book.",".$id_auth.");";
              $res = $this->db->query($sql);
              if(!$res)
                Throw new Exception('Something wrong! '.$sql);
            }
          }
        }
      }
    }
    else {
      $res = false;
      Throw new Exception("File ".$filename." is not readable or not exists");
    }
    
    $field_names = array(); 
  }
  
  $res = true;
} catch (Exception $e) {
  echo $e->getMessage();
}