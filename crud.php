<?php
$fp = fopen('/var/www/html/cgi/test.log', 'w');
fwrite($fp, "begin\n");
$entity_body = json_decode(file_get_contents('php://input'), true);
$schema = $entity_body['schema'];
fwrite($fp, "schema: $schema\n");
$table = $entity_body['table'];
fwrite($fp, "table: $table\n");
$mode = $entity_body['mode'];
fwrite($fp, "mode: $mode\n");
$data = $entity_body['data'];
fwrite($fp, "data: ".json_encode($data)."\n");
fwrite($fp, "got some data: $entity_body\n");

$sql = mysqli_connect("127.0.0.1", "pi", "raspberry", $schema);
if(!$sql) {
	echo json_encode("error");
	exit();
}

if ($mode == 'create') {
  $data_str = '';
  foreach ($data as $key => $value) {
    if (strlen($data_str)) {
      $data_str .= ', ';
    }
    $value_str = '';
    if (is_numeric($value)) {
      $value_str = $value;
    } else {
      $value_str = "'{$sql->escape_string(trim($value))}'";
    }
    $data_str .= "{$sql->escape_string(trim($key))} = $value_str";
  }
  $insert_query = 
    "Insert Into $table
     Set $data_str";
  fwrite($fp, "Running insert: $insert_query\n");
  $result = $sql->query($insert_query);
  $result_array = array();
  $result = $sql->query(
    "Select id
     From $table 
     Where id = LAST_INSERT_ID()");     
  while($row = $result->fetch_assoc()) {  
    $cur_result = new stdClass();
    $cur_result->id = $row['id'];
    $result_array[] = $cur_result;				
  }
} else if ($mode == 'read') {
  fwrite($fp, "in mode: read\n");
  $where_str = '';
  $data_str = '';
  foreach ($data as $key => $value) {
    fwrite($fp, "checking data: $key => ".json_encode($value)."\n");
    if ($key == 'where') {
      $where_str = 'Where ';
      foreach ($value as $joiner => $key_value) {
        fwrite($fp, "checking value: $joiner => ".json_encode($key_value)."\n");
        if (!in_array($joiner, ['and', 'or'])) {
          echo json_encode("error: bad joiner {$joiner}");
          exit();
        }
        foreach ($key_value as $i => $clause) {
          fwrite($fp, "checking key_value: $i => ".json_encode($clause)."\n");
          $column = $clause[0];
          $criteria = $clause[1];
          fwrite($fp, "checking value: $column => ".json_encode($criteria)."\n");
          $operator = '';
          if (in_array($criteria[0], ['=', '>=', '>', '<=', '<', '<>', 'is null', 'is not null', 'in', 'between', 'like'])) {
            $operator = $criteria[0];
          } else {
            echo json_encode("error: bad operator {$criteria[0]}");
            exit();
          }
          $value_str = '';
          if (is_numeric($criteria[1])) {
            $value_str = $criteria[1];
          } else {
            $value_str = "'{$sql->escape_string(trim($criteria[1]))}'";
          }
          if ($i > 0) {
            $where_str .= " $joiner ";
          }          
          $where_str .= " {$sql->escape_string(trim($column))} $operator $value_str "; 
        }
      }
    } else {
      $alias_str = '';
      if (strlen($value)) {
        fwrite($fp, "using alias: $value\n");
        $alias_str = "as '{$sql->escape_string(trim($value))}'";
      }
      if (strlen($data_str)) {
        $data_str .= ', ';
      }
      $data_str .= "{$sql->escape_string(trim($key))} $alias_str";
      fwrite($fp, "current data_str: $data_str\n");
    }
  }

  $result_array = array();
  $query = "Select $data_str
            From $table
            $where_str";
  fwrite($fp, "executing query: $query\n");
  $result = $sql->query($query);
  while($row = $result->fetch_assoc()) {  
    fwrite($fp, "looping results: ".implode(',', $row)."\n");
    $cur_result = new stdClass();
    fwrite($fp, "made cur_result\n");
    //$cur_result->id = $row['id'];
    fwrite($fp, "got id: {$row['id']}\n");
    foreach ($data as $key => $value) {
      fwrite($fp, "$key => $value\n");
      if ($key != 'where') {
        $alias_str = $key;
        if (strlen($value)) {
          $alias_str = $value;
        }
        fwrite($fp, "$key, $alias_str: {$row[$alias_str]}\n");
        $cur_result->$key = $row[$alias_str];
        fwrite($fp, "cur_result: ".json_encode($cur_result)."\n");
      }
    }
    $result_array[] = $cur_result;				
  }
} else if ($mode == 'update') {
  $data_str = '';
  $where_str = '';
  foreach ($data as $key => $value) {
    fwrite($fp, "checking data: $key => ".json_encode($value)."\n");
    if ($key == 'where') {
      $where_str = 'Where ';
      foreach ($value as $joiner => $key_value) {
        fwrite($fp, "checking value: $joiner => ".json_encode($key_value)."\n");
        if (!in_array($joiner, ['and', 'or'])) {
          echo json_encode("error: bad joiner {$joiner}");
          exit();
        }
        foreach ($key_value as $i => $clause) {
          fwrite($fp, "checking key_value: $i => ".json_encode($clause)."\n");
          $column = $clause[0];
          $criteria = $clause[1];
          fwrite($fp, "checking value: $column => ".json_encode($criteria)."\n");
          $operator = '';
          if (in_array($criteria[0], ['=', '>=', '>', '<=', '<', '<>', 'is null', 'is not null', 'in', 'between', 'like'])) {
            $operator = $criteria[0];
          } else {
            echo json_encode("error: bad operator {$criteria[0]}");
            exit();
          }
          $value_str = '';
          if (is_numeric($criteria[1])) {
            $value_str = $criteria[1];
          } else {
            $value_str = "'{$sql->escape_string(trim($criteria[1]))}'";
          }
          if ($i > 0) {
            $where_str .= " $joiner ";
          }          
          $where_str .= " {$sql->escape_string(trim($column))} $operator $value_str "; 
        }
      }
    } else {
      if (strlen($data_str)) {
        $data_str .= ', ';
      }
      $value_str = '';
      if (is_numeric($value)) {
        $value_str = $value;
      } else {
        $value_str = "'{$sql->escape_string(trim($value))}'";
      }
      $data_str .= "{$sql->escape_string(trim($key))} = $value_str";
      fwrite($fp, "current data_str: $data_str\n");
    }
  }
  $update_query = 
    "Update $table
     Set $data_str
     $where_str";
  fwrite($fp, "Running update: $update_query\n");
  $result = $sql->query($update_query);

  $result_array = array();
  $result = $sql->query(
    "Select id
     From $table 
     $where_str");     
  while($row = $result->fetch_assoc()) {  
    $cur_result = new stdClass();
    $cur_result->id = $row['id'];
    $result_array[] = $cur_result;				
  }
} else if ($mode == 'delete') {
  $where_str = '';
  foreach ($data as $key => $value) {
    fwrite($fp, "checking data: $key => ".json_encode($value)."\n");
    if ($key == 'where') {
      $where_str = 'Where ';
      foreach ($value as $joiner => $key_value) {
        fwrite($fp, "checking value: $joiner => ".json_encode($key_value)."\n");
        if (!in_array($joiner, ['and', 'or'])) {
          echo json_encode("error: bad joiner {$joiner}");
          exit();
        }
        foreach ($key_value as $i => $clause) {
          fwrite($fp, "checking key_value: $i => ".json_encode($clause)."\n");
          $column = $clause[0];
          $criteria = $clause[1];
          fwrite($fp, "checking value: $column => ".json_encode($criteria)."\n");
          $operator = '';
          if (in_array($criteria[0], ['=', '>=', '>', '<=', '<', '<>', 'is null', 'is not null', 'in', 'between', 'like'])) {
            $operator = $criteria[0];
          } else {
            echo json_encode("error: bad operator {$criteria[0]}");
            exit();
          }
          $value_str = '';
          if (is_numeric($criteria[1])) {
            $value_str = $criteria[1];
          } else {
            $value_str = "'{$sql->escape_string(trim($criteria[1]))}'";
          }
          if ($i > 0) {
            $where_str .= " $joiner ";
          }          
          $where_str .= " {$sql->escape_string(trim($column))} $operator $value_str "; 
        }
      }
    }
  }
  if (strlen($where_str) > 0) {
    $delete_query = 
      "Delete from $table
       $where_str";
     fwrite($fp, "Running delete: $delete_query\n");
    $result = $sql->query($update_query);
  }
  $result_array = [];
}

echo json_encode($result_array);
fclose($fp);
?>