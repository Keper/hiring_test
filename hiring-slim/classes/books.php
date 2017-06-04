<?php

class books {
  
  // Database connection
  private $db;
  // Number of records per page, default 30 ite per page
  private $num_rec;
  // Flag for adult books
  private $flg_adult;
  // Offset of pagination start 0
  private $offset;
  // Logger from slim
  public $logger;
  
  public function __construct($db) {
    
    $this->db = $db;
    $this->num_rec = 30;
    $this->offset = 0;
    $this->flg_adult = false;
  }

  /**
   * If parameter is empty get the number of records per page else set the number of records
   * 
   * @param integer $num_rec 
   * @return integer
   */
  public function numRec($num_rec = null) {
    
    if(!is_null($num_rec))
      $this->num_rec = $num_rec;
    else
      return $this->num_rec;
  }
  /**
   * If parameter is empty get the status of flg_adult else set the boolean value
   *
   * @param boolean flg_adult
   * @return integer (0 -> false, 1-> true)
   */
  public function flgAdult(bool $flg_adult = null) {
    
    if(!is_null($flg_adult) && is_bool($flg_adult))
      $this->flg_adult= flg_adult;
    else {
      $tmp_flg = $this->flg_adult === true ? 1 : 0;
      return $tmp_flg;
    }
      
  }
  
  public function getTableData($by_flg = true, $search = '')
  {
    
    $ret = array();
    
    if($by_flg == false) {
      $sql_add = " AND LOWER(title) like '%".$this->db->real_escape_string(strtolower($search))."%'";
      $limit = "";
    }
    else { 
      $sql_add = "";
      $limit = "LIMIT ".$this->num_rec."
                OFFSET ".$this->offset.";";
    }
    
    // Books and category
    $sql = "SELECT 
              id_book,
              title, 
              desc_category
            FROM 
              books a,
              category b
            WHERE 1=1
              AND a.id_category = b.id_category
              AND flg_xxx = ".$this->flgAdult()."
            ".$sql_add."
            ORDER BY a.title
            ".$limit; 

    $res = $this->db->query($sql);
    
    if($res) {
      while ($row = $res->fetch_assoc()) {
        
        $books = $row;
        $authors = $this->getAuthorsBook($row['id_book']);
        
        $books['authors'] = $authors;
        $ret[] = $books;
      }
    }
    
    return $ret;
  }
  
  /**
   * Calculating number of page of paginator (max 5 pages then ... 5 last pages )
   * 
   * @param number $start
   * @return array 
   */
  public function paginator($start = 1) {
    
    if($this->numPage()-$start < 4 )
      $interval = $this->numPage()-$start;
    else
      $interval = 4;
    
    $page_range = range($start, $start + $interval);
    
    return $page_range;
  }

  /**
   * Read the link from paginator and recalculating the paginator range and rebuild html
   * 
   * @param string $link
   */
  public function changePaginator($link) {
    
    $new_paginator = '';
    
    if(!is_numeric($link)){
      $this->logger->info('passo di qui anche per i numeri?');
      switch ($link) {
        case 'first':
          $_SESSION['page'] = 1;
          break;
        case 'prev':
          if($_SESSION['page'] > 1) 
            --$_SESSION['page'];
          break;
        case 'next':
          if($_SESSION['page'] < $this->numPage())
            ++$_SESSION['page'];
          break;
        case 'last':
          $_SESSION['page'] = $this->numPage();
          break;
      }
    }
    else {
      $_SESSION['page'] = intval($link);
      $this->logger->info(intval($link));
    }
    $this->logger->info($_SESSION['page']);

    
    $new_range_start = $this->paginator($_SESSION['page']);
    $new_range_end = $this->paginator($this->numPage()-4);
    $flg_dots = true;
    
   
    // If the start page is in the first range i need to calculate the correct number of page to view to avoid double page
    if($new_range_start[0] >= $this->numPage() - 9 && $new_range_start[0] <= $this->numPage() - 5) {
      $num_to_mantain = $this->numPage()-$new_range_start[0]-5;
      foreach($new_range_start as $key=>$val) {
        if($key > $num_to_mantain)
          unset($new_range_start[$key]);
      }
    } elseif ($new_range_start[0] >= $this->numPage() - 4) {
      
      $new_range_end = array();
      $flg_dots = false;
    }
    
    $html = '';
    foreach($new_range_start as $page) {
      $html.= '<a class="page" href="javascript:void(0);">  '.$page.'  </a>';
    }
    if(count($new_range_end) > 0){
      $html.= '...';
      foreach($new_range_end as $page) {
        $html.= '<a class="page" href="javascript:void(0);">  '.$page.'  </a>';
      }
    }
    
    return $html;
    
  }
  
  /**
   * rebuild html of table calculating the data from paginator
   */
  public function changeTable() {
    
    $this->offset = ($_SESSION['page']-1)*$this->num_rec;
    
    $data = $this->getTableData();
    
    $html = '
            <table>
           		<tr>
            		<th>Title</th>
            		<th>Author</th>
            		<th>Category</th>
            	</tr>';
    foreach($data as $book) {
      $html .= '<tr>
                  <td>'.htmlspecialchars($book['title']).'</td>
                  <td>
              ';
            		
      foreach($book['authors'] as $author) {
        $html .= htmlspecialchars($author).'</br>';
      }
      
      $html .= '</td>
                <td>'.htmlspecialchars($book['desc_category']).'</td>
                </tr>';
    }
    
    $html .= '</table>';
    
    return $html;
  }
  
  public function searchAuthors($search) {
    
    $ret = array();
    
    $sql = "SELECT 
              b.id_book, 
              authorname 
            FROM 
              authors a,
              books_authors b  
            WHERE 1=1
              AND LOWER(authorname) like '%".$this->db->real_escape_string(strtolower($search))."%'
              AND a.id_author = b.id_author;";
    $res = $this->db->query($sql);
    
    if($res) {
      
      while ($row = $res->fetch_assoc()) {
        
       $sql = "SELECT 
              title, 
              desc_category
            FROM 
              books a,
              category b
            WHERE 1=1
              AND a.id_category = b.id_category
              AND flg_xxx = ".$this->flgAdult()."
              AND id_book = ".$row['id_book']."
            ORDER BY a.title ";
       $res_book = $this->db->query($sql);
       
        while ($row_book = $res_book->fetch_assoc()) {
          
          
          $books = $row_book;
          $books['authors'][] = $row['authorname'];
        }
        
        $ret[] = $books;
      }
    }
    
    return $ret;
  }
  
  public function search($search) {
   
   $books = $this->getTableData(false, $search);
   $authors = $this->searchAuthors($search);
   
   $ret = array_merge_recursive($books, $authors);
   
   return $ret;
   
  }
  
  
  public function toggleFlgAdult() {
    
    $this->flg_adult = !$this->flg_adult;    
  }
  
  /**
   * If adult filter is disable return Enable, else Hide
   * 
   * @return string
   */
  public function labelFlgAdult() {
    
    $filter = $this->flg_adult ? 'Hide adult books': 'Show adult books only';
    
    return $filter;
    
  }
  
  /**
   * Get the total record for adult e not-adult versions
   * 
   * @return integer total record of books table without adult books
   */
  private function getTotRec() {
    
   
    $sql = "SELECT id_book FROM books WHERE id_category IN (SELECT id_category FROM category WHERE flg_xxx = ".$this->flgAdult().");";
    $res = $this->db->query($sql);
    
    return $res->num_rows;
     
  }
  
  
  public function numPage() {
    
    $tot_rec = $this->getTotRec();

    $num_page = $tot_rec / $this->num_rec;
    $tmp = intval($tot_rec / $this->num_rec);
    
    if($tmp == $num_page) 
      return $num_page;
    else 
      return intval($num_page)+1;
  }
  
  private function getAuthorsBook($id_book) {
    
    $sql = "SELECT
                authorname
              FROM
                authors a,
                books_authors b
              WHERE 1=1
                AND a.id_author = b.id_author
                AND b.id_book = ".$id_book.";
             ";
    $res = $this->db->query($sql);
    while ($row = $res->fetch_assoc()) {
      
      $authors[] = $row['authorname'];
    }
    
    return $authors;
    
  }
  
  
}