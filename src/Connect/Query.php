<?php

namespace EASY;

class Query{

	public $conn;

	function __construct($conn){
		$this->conn = $conn;
	}

	/**
	 * @method selectWhere
	 * @param $table
	 * @param $where
	*/

	protected function convert($value, $type){

		if($type == 'array'){
			$return = $value;
		}else if($type == 'json'){
			$return = json_encode($value);
		}else if($type == 'debug'){
			$return = var_dump($value);
		}

		return $return;

	}

	public function selectWhere($table, $where){

		$stmt = $this->conn->prepare("SELECT * FROM $table WHERE $where");
		$stmt->execute();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function selectWhereDist($table,$dist, $where){

		$stmt = $this->conn->prepare("SELECT DISTINCT $dist FROM $table WHERE $where");
		$stmt->execute();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * @method selectById
	 * @param $table
	 * @param $id
	*/

	public function selectById($table, $id){

		$stmt = $this->conn->prepare("SELECT * FROM $table WHERE id = ?");
		$stmt->bindParam(1, $id, \PDO::PARAM_INT);
		$stmt->execute([$id]);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function deletebyId($table ,$id){

		$stmt = $this->conn->prepare("DELETE FROM $table WHERE id = :ID");

		$stmt->bindParam(":ID", $id);

		$stmt->execute();
		return;

	}


	/**
	 * @method selectAll
	 * @param $table
	*/

	public function selectAll($table){

		$stmt = $this->conn->prepare("SELECT * FROM $table");
		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);

	}

	private	function data($select,$type){

		if($type == 'fetch'){

			$select = $this->conn->prepare($select);
			$select->execute();
			$info = $select->fetch();

		}else if($type == 'fetchAll'){

			$select = $this->conn->prepare($select);
			$select->execute();
			$info = $select->fetchAll(\PDO::FETCH_ASSOC);

		}else if($type == 'insert'){

			$select = $this->conn->prepare($select);
			$info = $select->execute();

		}else if($type == 'update'){

			$select = $this->conn->prepare($select);
			$info = $select->execute();

		}

		return $info;
	}

	private function magicSelect($table){

		$select = "DESCRIBE $table";
		return self::data($select,"fetchAll");

	}

	private function findTable($table){

		$search = self::magicSelect($table);
		$count  = count($search);

		for ($i=0; $i < $count ; $i++) {

			if($search[$i]['Field'] !== "id"){

				$result[] = $search[$i]['Field'];
			}
		}

		return $result;
	}

	//REMOVE CARACTERES HTML
/*	public function strip_tags_content($text, $tags = '', $invert = FALSE) { 
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
		$tags = array_unique($tags[1]); 

		if(is_array($tags) AND count($tags) > 0) { 
			if($invert == FALSE) { 
				return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text); 
			} 
			else { 
				return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
			} 
		} 
		elseif($invert == FALSE) { 
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
		} 
		return $text; 
	}*/

	public function Query($table, $array, $type, $where){

		$tableInfo 	= self::findTable($table);
		$count 		= count($tableInfo);
		$fieldA 	= "";
		$fieldB 	= "";
		$field  	= "";

 //CASO SEJA insert =========================== * * *

		if($type == "insert"){

			foreach ($tableInfo as $index => $value) {

				if($index+1 < $count){

					$fieldA .= $value . ", ";
					$fieldB .= ":".strtoupper($value).", ";
					$fieldAarray[] = $value;
					$fieldBarray[] = ":".strtoupper($value);

				}else{

					$fieldA .= $value;
					$fieldB .= ":".strtoupper($value);
					$fieldAarray[] = $value;
					$fieldBarray[] = ":".strtoupper($value);
				}

			}

			/*
			example $fieldA and $fieldAarray
			column01, column02, column03

			example $fieldB and $fieldBarray
			:COLUMN01, :COLUMN02, :COLUMN03

			final example
			$stmt = $this->conn->prepare("INSERT INTO $table (column01, column02, column03) VALUES (:COLUMN01, :COLUMN02, :COLUMN03) $where");
			*/
			$stmt = $this->conn->prepare("INSERT INTO $table ($fieldA) VALUES ($fieldB) $where");

			for ($i=0; $i < count($fieldAarray) ; $i++) {

				/*
				--> example $fieldBarray[$i]
				:COLUMN01

				example $array[$fieldAarray[$i]]
				$array['column01'] = myValue

				final example
				$stmt->bindParam(:COLUMN01, myValue); ...
				*/
				$stmt->bindParam($fieldBarray[$i], $array[$fieldAarray[$i]]);
			}

			$stmt->execute();

 //CASO SEJA update =========================== * * *

		}else if($type == "update"){

			foreach ($tableInfo as $index => $value) {

				if($index+1 < $count){

					$field .= $value . " = :".strtoupper($value).", ";
					$fieldAarray[] = ":".strtoupper($value);
					$fieldBarray[] = $value;

				}else{

					$field .= $value . " = :".strtoupper($value);
					$fieldAarray[] = ":".strtoupper($value);
					$fieldBarray[] = $value;
				}

			}

			/*
			example $field
			column01 = :COLUMN01 , column02 = :COLUMN02, column03 = :COLUMN03

			example $fieldAarray
			:COLUMN01, :COLUMN02, :COLUMN03

			example $fieldBarray
			column01, column02, column03

			final example
			$stmt = $this->conn->prepare("UPDATE $table SET column01 = :COLUMN01 , column02 = :COLUMN02, column03 = :COLUMN03 WHERE $where");

			*/

			$stmt = $this->conn->prepare("UPDATE $table SET $field WHERE $where");


			for ($i=0; $i < count($fieldAarray) ; $i++) {
				/*
				example $fieldAarray[$i]
				:COLUMN01

				example $array[$fieldAarray[$i]]
				$array['column01'] = myValue

				final example
				$stmt->bindParam(:COLUMN01, myValue); ...

				*/

				$stmt->bindParam($fieldAarray[$i], $array[$fieldBarray[$i]]);
			}

			return $stmt->execute();

		}

		return;

	}

	public function selectByDate($table, $column, $start, $end, $condition){

		$query = "SELECT * FROM $table WHERE $column BETWEEN DATE('$start') AND DATE('$end')";

		return self::data($query,"fetchAll");

	}

}