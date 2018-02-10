<?php
namespace FHMJ\PdoFlexibleSearch;

class Driver{
	/*******************************
	* SETUP
	*******************************/
	public $columnSeparator = ';'; // separate multiple columns
	public $tokenSeparator = '/'; // separate search token from column
	
	public $defaultToken = 'E'; // use this as default, if no search token is defined
	
	// search token-to-operator mapping
	public $searchTokens = [
		'E' => '=',
		'!E' => '!=',
		'GT' => '>',
		'!GT' => '<=',
		'GTE' => '>=',
		'!GTE' => '<',
		'LT' => '<',
		'!LT' => '>=',
		'LTE' => '<=',
		'!LTE' => '>',
		'L' => 'LIKE',
		'!L' => 'NOT LIKE',
		'IS' => 'IS',
		'!IS' => 'IS NOT'
	];
	
	// search tokens that requires some "processing"
	public $processSearchTokens = [
		'BT' => 'betweenOperator',
		'!BT' => 'notBetweenOperator'
	];
	
	
	/*******************************
	* PROCESSED TOKENS
	*******************************/
	/**
	* Between Operator
	*
	* Generate condition "column BETWEEN values[0] AND values[1]".
	* Works only with values in even pair (2, 4, 6).
	* If an uneven amount of values are given, ignore the last value.
	*/
	protected function betweenOperator(string $column, array $values, string $operator = 'BETWEEN'): string{
		$parsedValues = [];
		
		$pair = [];
		foreach($values as $value){
			$pair[] = $value;
			if(count($pair) === 2){
				$parsedValues[] = $pair[0].' AND '.$pair[1];
				$pair = [];
			}
		}
		
		return $this->operator($column, $parsedValues, $operator);
	}
	
	/**
	* Not Between Operator
	*
	* Generate condition "column NOT BETWEEN values[0] AND values[1]".
	* Works only with values in even pair (2, 4, 6).
	* If an uneven amount of values are given, ignore the last value.
	*/
	protected function notBetweenOperator(string $column, array $values): string{
		return $this->betweenOperator($column, $values, 'NOT BETWEEN');
	}
	
	
	/*******************************
	* METHODS
	*******************************/
	/**
	* Search
	*
	* Generates sql where clause with given conditions.
	*/
	public function search(array $andConditions, array $orConditions = []): array{
		// throw if both and & or conditions are empty
		if(!$andConditions && !$orConditions){
			throw new \InvalidArgumentException('search requires at least one condition');
		}
		
		$output = [
			'placeholders' => [],
			'whereClause' => ''
		];
		
		$placeholderCount = 0; // prevents duplicate placeholder naming
		
		$conditions = [];
		if($andConditions){
			$conditions[] = [
				'glue' => 'AND',
				'entries' => $andConditions
			];
		}
		if($orConditions){
			$conditions[] = [
				'glue' => 'OR',
				'entries' => $orConditions
			];
		}
		
		foreach($conditions as $condition){
			$whereClause = [];
			
			// for each search condition
			foreach($condition['entries'] as $column => $values){
				$values = $this->flattenArray((array) $values);
				
				// replace values with a pdo placeholder
				foreach($values as $i => $value){
					$key = 'pdoFlexibleSearch'.$placeholderCount++;
					$output['placeholders'][$key] = $value;
					$values[$i] = ':'.$key;
				}
				
				$conditionGroup = [];
				// for each column in condition
				foreach($this->parseColumnString($column) as $columnData){
					if(!$columnData['token']){
						$columnData['token'] = $this->defaultToken;
					}
					
					if(!empty($this->searchTokens[$columnData['token']])){
						$conditionGroup[] = $this->operator($columnData['column'], $values, $this->searchTokens[$columnData['token']]);
					}
					else if(!empty($this->processSearchTokens[$columnData['token']])){
						$method = $this->processSearchTokens[$columnData['token']];
						$conditionGroup[] = $this->$method($columnData['column'], $values);
					}
					else{
						throw new \InvalidArgumentException('use of unknown search token `'.$columnData['token'].'`');
					}
				}
				
				$whereClause[] = '('.implode(' OR ', $conditionGroup).')';
			}
			
			if(!$output['whereClause']){
				$output['whereClause'] .= implode(PHP_EOL.$condition['glue'].' ', $whereClause);
			}
			else{
				$output['whereClause'] .= PHP_EOL.'AND ( '.implode(PHP_EOL.$condition['glue'].' ', $whereClause).' )';
			}
		}
		
		return $output;
	}
	
	/**
	* Operator
	*
	* Generate a sql operator.
	*/
	public function operator(string $column, array $values, string $operator): string{
		$sql = [];
		
		if(!$column){
			throw new \InvalidArgumentException('column must not be empty');
		}
		if(!$values){
			throw new \InvalidArgumentException('operator generation requires at least one value');
		}
		
		foreach($values as $value){
			if($value === null){
				$value = 'NULL';
			}
			else if(is_bool($value)){
				$value = (int) $value;
			}
			else if(!is_scalar($value)){
				throw new \InvalidArgumentException('value must be NULL or a scalar value');
			}
			else if(trim($value) === ''){
				throw new \InvalidArgumentException('value must not be empty');
			}
			
			$sql[] = $column.' '.$operator.' '.$value;
		}
		
		return implode(' OR ', $sql);
	}
	
	/**
	* Parse Column String
	*
	* Split columns & search tokens into an array.
	*/
	public function parseColumnString(string $columnString): array{
		$columnData = [];
		
		foreach(explode($this->columnSeparator, $columnString) as $column){
			$column = explode($this->tokenSeparator, $column);
			if(count($column) === 1){
				$columnData[] = [
					'token' => '',
					'column' => reset($column)
				];
			}
			else{
				$columnData[] = [
					'token' => reset($column),
					'column' => end($column)
				];
			}
		}
		
		return $columnData;
	}
	
	/**
	* Merge Column Data
	*
	* Merges array from parseColumnString back into a string.
	*/
	public function mergeColumnData(array $columnData): string{
		$output = [];
		
		foreach($columnData as $data){
			$values = [];
			if(!empty($data['token'])){
				$values[] = $data['token'];
			}
			if(!empty($data['column'])){
				$values[] = $data['column'];
			}
			
			$output[] = implode($this->tokenSeparator, $values);
		}
		
		return implode($this->columnSeparator, $output);
	}
	
	/**
	* Flatten Array
	*
	* Recursive function for "flattening" a multi-dimensional array into
	* a "flat" array.
	*/
	protected function flattenArray(array $values): array{
		$output = [];
		
		foreach($values as $value){
			if(!is_array($value)){
				$output[] = $value;
			}
			else{
				foreach($this->flattenArray($value) as $subValue){
					$output[] = $subValue;
				}
			}
		}
		
		return $output;
	}
}
