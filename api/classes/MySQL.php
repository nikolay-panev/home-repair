<?php
class APIMySQL {
	public $db = null;
	protected $allowed_methods = ['=', '>', '<', '>=', '<=', '<>', '!=', 'is', 'is not', 'in', 'not in', 'like', 'not like'];
    protected $map = ['eq' => '=', 'neq' => '!=', 'less' => '<', 'more' => '>', 'leq' => '<=', 'meq' => '>=', 'diff' => '<>', 'is' => 'IS', 'isnt' => 'IS NOT', 'like' => 'LIKE', 'nlike' => 'NOT LIKE', 'in' => 'IN', 'nin' => 'NOT IN'];
	
	public function __construct($db) {
		$this->db = $db;
	}
	public function get($data) {
		if($data['operation'] == 'select') return $this->resolveSelect($data);
		else if($data['operation'] == 'update') return $this->resolveUpdate($data);
		else if($data['operation'] == 'insert') return $this->resolveInsert($data);
		else if($data['operation'] == 'delete') return $this->resolveDelete($data);
	}
	private function resolveSelect($data) {
		// SELECT [DISTINCT|ALL ] { * | [fieldExpression [AS newName]} FROM tableName [alias] LEFT JOIN table2Name ON tableName.field=table2Name.field [WHERE condition][GROUP BY fieldName(s)]  [HAVING condition] ORDER BY fieldName(s) LIMIT offset,limit
		$page = null;
        $limit = null;
		$offset = null;
		
		$sql = 'SELECT ';
		foreach($data['fields'] as $field) {
			if(is_string($field)) $sql .= $field .',';
			if(is_array($field)) $sql .= '('.$this->getSelectQuery($field) . ') AS `' . $field['table'] . '.' . $field['fields'][0] . '`,';
		}
		$sql = rtrim($sql, ',');
		$sql .= ' FROM ' . $data['table'];
		
		if(array_key_exists('where', $data)) {
			if($data['where'] && is_array($data['where']) && count($data['where']) > 0) {
				$sql .= ' WHERE ' . $this->where($data['where']);
			}
		}
		if(array_key_exists('group', $data)) {
			if($data['group'] && is_array($data['group']) && count($data['group']) > 0) {
				$sql .= ' GROUP BY ' . implode(',', $data['group']);
			}
		}
		if(array_key_exists('order', $data)) {
			if($data['order'] && is_array($data['order']) && count($data['order']) > 0) {
				$sql .= ' ORDER BY ';
				
				foreach($data['order'] as $field => $direction) {
					$sql .= $field . ' ' . $direction .',';
				}
				$sql = rtrim($sql, ',');
			}
		}
		if(array_key_exists('page', $data)) {
			if(isset($data['page']) && @$data['page'] - 0 > 0) $page = @$data['page'] - 1;
		}
		if(array_key_exists('limit', $data)) {
			if(isset($data['limit']) && @$data['limit'] - 0 > 0) $limit = @$data['limit'] - 0;
		}
		if(array_key_exists('opts', $data)) {
			if(isset($data['opts']) && is_array($data['opts']) && count($data['opts']) > 0) {
				if(isset($data['opts']['page']) && @$data['opts']['page'] - 0 > 0) $page = @$data['opts']['page'] - 1;
				if(isset($data['opts']['itemsPerPage']) && @$data['opts']['itemsPerPage'] - 0 > 0)  $limit = @$data['opts']['itemsPerPage'] - 0;

				if(isset($data['opts']['sortBy']) && is_array($data['opts']['sortBy']) && count($data['opts']['sortBy']) > 0) {
					$sql .= ' ORDER BY ';
					
					foreach($data['opts']['sortBy'] as $key => $field) {
						//if(in_array($field, $data['fields'])) {
							$direction = 'ASC';
							if(isset($data['opts']['sortDesc'][$key]) && $data['opts']['sortDesc'][$key]) $direction = 'DESC';
							$sql .= $field . ' ' . $direction .',';
						//}
					}
					$sql = rtrim($sql, ',');
				}
			}
		}
		// count total records before applying LIMIT
		$sql2 = 'SELECT COUNT(*) AS total FROM ('.$sql.') t';
		
		if($limit !== null) {
            if(!($page > 0)) $page = 0;
            if($limit < 0) $limit = 0;

            $offset = $page * $limit;
            if($offset > 0) $sql .= ' LIMIT ' . $offset . ',' . $limit;
            else $sql .= ' LIMIT ' . $limit;
        }
		
		$stmt = $this->db->prepare($sql2);
		$stmt->execute();
		$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
		
		$results = [];
		if($totalRecords > 0) {
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		
		return ['debug' => SQL_DEBUG ? [$sql, $sql2] : null, 'data'=>$results, 'totalRecords'=>intval($totalRecords)];
	}
	private function getSelectQuery($data) {
		// SELECT [DISTINCT|ALL ] { * | [fieldExpression [AS newName]} FROM tableName [alias] LEFT JOIN table2Name ON tableName.field=table2Name.field [WHERE condition][GROUP BY fieldName(s)]  [HAVING condition] ORDER BY fieldName(s) LIMIT offset,limit
		$page = null;
        $limit = null;
		$offset = null;
		
		$sql = 'SELECT ';
		foreach($data['fields'] as $field) {
			if(is_string($field)) $sql .= $field .',';
			if(is_array($field)) $sql .= '('.$this->getSelectQuery($field) . ') AS ' . $field['fields'][0] . ',';
		}
		$sql = rtrim($sql, ',');
		$sql .= ' FROM ' . $data['table'] . ' ' . $data['table_alias'];
		
		if(array_key_exists('where', $data)) {
			if($data['where'] && is_array($data['where']) && count($data['where']) > 0) {
				$sql .= ' WHERE ' . $this->where2($data['where']);
			}
		}
		if(array_key_exists('group', $data)) {
			if($data['group'] && is_array($data['group']) && count($data['group']) > 0) {
				$sql .= ' GROUP BY ' . implode(',', $data['group']);
			}
		}
		if(array_key_exists('order', $data)) {
			if($data['order'] && is_array($data['order']) && count($data['order']) > 0) {
				$sql .= ' ORDER BY ';
				
				foreach($data['order'] as $field => $direction) {
					$sql .= $field . ' ' . $direction .',';
				}
				$sql = rtrim($sql, ',');
			}
		}
		if(array_key_exists('page', $data)) {
			if(isset($data['page']) && @$data['page'] - 0 > 0) $page = @$data['page'] - 1;
		}
		if(array_key_exists('limit', $data)) {
			if(isset($data['limit']) && @$data['limit'] - 0 > 0) $limit = @$data['limit'] - 0;
		}
		if(array_key_exists('opts', $data)) {
			if(isset($data['opts']) && is_array($data['opts']) && count($data['opts']) > 0) {
				if(isset($data['opts']['page']) && @$data['opts']['page'] - 0 > 0) $page = @$data['opts']['page'] - 1;
				if(isset($data['opts']['itemsPerPage']) && @$data['opts']['itemsPerPage'] - 0 > 0)  $limit = @$data['opts']['itemsPerPage'] - 0;

				if(isset($data['opts']['sortBy']) && is_array($data['opts']['sortBy']) && count($data['opts']['sortBy']) > 0) {
					$sql .= ' ORDER BY ';
					
					foreach($data['opts']['sortBy'] as $key => $field) {
						if(in_array($field, $data['fields'])) {
							$direction = 'ASC';
							if(isset($data['opts']['sortDesc'][$key]) && $data['opts']['sortDesc'][$key]) $direction = 'DESC';
							$sql .= $field . ' ' . $direction .',';
						}
					}
					$sql = rtrim($sql, ',');
				}
			}
		}
		if($limit !== null) {
            if(!($page > 0)) $page = 0;
            if($limit < 0) $limit = 0;

            $offset = $page * $limit;
            if($offset > 0) $sql .= ' LIMIT ' . $offset . ',' . $limit;
            else $sql .= ' LIMIT ' . $limit;
        }
		
		return $sql;
	}
	private function resolveInsert($data) {
		$id = [];
		$params = [];
		if($data['data'] && is_array($data['data']) && count($data['data']) > 0) {
			foreach($data['data'] as $row) {
				$sql = 'INSERT INTO `' . $data['table'] . '` (`' . implode('`, `', array_keys($row)) . '`) VALUES (:'.implode(', :', array_keys($row)).')';
				$stmt = $this->db->prepare($sql);
				
				foreach($row as $key=>$value) {
					if(is_array($value) || is_object($value)) {
						$params[] = $key.'='.json_encode($value);
						$stmt->bindValue(":".$key, json_encode($value));
					}
					else {
						$params[] = $key.'='.$value;
						if($key == 'password') $stmt->bindValue(":$key", password_hash($value, PASSWORD_DEFAULT));
						else $stmt->bindValue(":$key", $value);
					}
				}
				$stmt->execute();
				$id[] = $this->db->lastInsertId();
			}
		}

		return ['debug'=> SQL_DEBUG ? [$sql] : null, 'params'=>$params, 'inserted'=>$id];
	}
	private function resolveUpdate($data) {
		if($data['data'] && is_array($data['data']) && count($data['data']) > 0) {
			$debug = [];
			
			foreach($data['data'] as $row) {
				
				if(!$row['id']) {
					$debug[] = ['error' => 'Missing ID'];
					continue;
				}
		
				$sql = 'UPDATE ' . $data['table'] . ' SET ';
		
				foreach($row as $field => $value) {
					if($field == 'id') continue;
					
					$sql .= $field . ' = :' . $field . ',';
				}
				$sql = rtrim($sql, ',');
				
				$sql .= ' WHERE id = :the_id';
				
				$debug[] = $sql;
			
				$stmt = $this->db->prepare($sql);
			
				foreach($row as $field => $value) {
					if($field == 'id') continue;
					if($field == 'password') $stmt->bindValue(":".$field, password_hash($value, PASSWORD_DEFAULT));
					else $stmt->bindValue(":".$field, $value);
				}
				$stmt->bindValue(":the_id", $row['id']);
				$result = $stmt->execute();
			}
		}
		
		return ['debug' => SQL_DEBUG ? $debug : null, 'data'=>$data['data'], 'result' => $result];
	}
	private function resolveDelete($data) {
		if($data['where'] && is_array($data['where']) && count($data['where']) > 0) {
			//$sql = 'DELETE FROM ' . $data['table'] . ' WHERE '.$this->where($data['where']);
			$sql = 'UPDATE `'.$data['table'] . '` SET `deleted`="'.date('Y-m-d H:i:s').'" WHERE '.$this->where($data['where']);
			
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			
			return ['debug' => SQL_DEBUG ? [$sql] : null];
		}
		
		return [];
	}
	private function where($args=[]) {
		$wh = "";
        if(is_array($args) && count($args) > 0) {
			$w = [];
            foreach($args as $arg) {
                foreach($arg as $field => $condObj) {
                    if($field == "OR") {
                        //$type = 'OR';
                        $or = $this->whereOR($condObj);
						$w[] = ['AND' => $or];
						
						//$wh .= ' OR (' . $or . ')';
                    }
                    else if($field == "AND") {
						//$type = 'AND';
                        $and = $this->where($condObj);
						$w[] = ['AND' => $and];
						
						//$wh .= ' AND (' . $and . ')';
                    }
                    else {
						//$type = 'AND';
                        $op = @$this->map[key($condObj)];
                        $val =  @$condObj[key($condObj)] == null ? "NULL" : (is_array(@$condObj[key($condObj)]) ? '(' . implode(',', @$condObj[key($condObj)]) . ')' : ($this->isSubquery(@$condObj[key($condObj)]) ? @$condObj[key($condObj)] : '"'.@$condObj[key($condObj)].'"'));
                        
						$w[] = ["AND" => ($field . ' '. $op . ' ' . $val)];
                    }
                }
            }
			
			foreach($w as $item) {
				foreach($item as $key => $where_str) {
					if($wh != "") $wh .= ' ' . $key . ' ' . $where_str;
					else $wh = $where_str;
				}
			}
        }
		
		return $wh;
    }
	public function where2($args=[]) {
		$wh = "";
        if(is_array($args) && count($args) > 0) {
			$w = [];
            foreach($args as $arg) {
                foreach($arg as $field => $condObj) {
                    if($field == "OR") {
                        //$type = 'OR';
                        $or = $this->whereOR($condObj);
						$w[] = ['AND' => $or];
						
						//$wh .= ' OR (' . $or . ')';
                    }
                    else if($field == "AND") {
						//$type = 'AND';
                        $and = $this->where($condObj);
						$w[] = ['AND' => $and];
						
						//$wh .= ' AND (' . $and . ')';
                    }
                    else {
						//$type = 'AND';
                        $op = @$this->map[key($condObj)];
                        $val =  @$condObj[key($condObj)] == null ? "NULL" : @$condObj[key($condObj)];
                        
						$w[] = ["AND" => ($field . ' '. $op . ' ' . $val)];
                    }
                }
            }
			
			foreach($w as $item) {
				foreach($item as $key => $where_str) {
					if($wh != "") $wh .= ' ' . $key . ' ' . $where_str;
					else $wh = $where_str;
				}
			}
        }
		
		return $wh;
    }
	private function whereOR($args=[]) {
		$wh = "";
        if(is_array($args) && count($args) > 0) {
			$w = [];
            foreach($args as $arg) {
				foreach($arg as $field => $condObj) {
					$op = @$this->map[key($condObj)];
                    $val =  @$condObj[key($condObj)] == null ? "NULL" : (is_array(@$condObj[key($condObj)]) ? '(' . implode(',', @$condObj[key($condObj)]) . ')' : ($this->isSubquery(@$condObj[key($condObj)]) ? @$condObj[key($condObj)] : '"'.@$condObj[key($condObj)].'"'));
                    
					$w[] = ["OR" => ($field . ' '. $op . ' ' . $val)];
				}
			}
			
			foreach($w as $item) {
				foreach($item as $key => $where_str) {
					if($wh != "") $wh .= ' ' . $key . ' ' . $where_str;
					else $wh = "(" . $where_str;
				}
			}
		}
		$wh .= ")";
		return $wh;
	}
	private function isSubquery($str) {
		return (stripos($str, "SELECT") !== false && stripos($str, "FROM") !== false);
	}
}