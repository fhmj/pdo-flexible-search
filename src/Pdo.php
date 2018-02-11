<?php
namespace FHMJ\PdoFlexibleSearch;

class Pdo extends \PDO{
	/*******************************
	* SETUP
	*******************************/
	private $searchConditions = [];
	
	
	/*******************************
	* METHODS
	*******************************/
	/**
	* Search
	*
	* Define search placeholder and conditions.
	*/
	public function search(string $placeholderName = '', array $andConditions, array $orConditions = []){
		$this->searchConditions = [
			'placeholder' => trim($placeholderName, ':'),
			'conditions' => [$andConditions, $orConditions]
		];
		
		return $this;
	}
	
	/**
	* Execute
	*/
	public function exec($statement){
		if(!$this->searchConditions){
			return parent::exec($statement);
		}
		
		$query = $this->injectSearchIntoStatement($statement);
		$query->execute();
		return $query->rowCount();
	}
	
	/**
	* Prepare
	*/
	public function prepare($statement, $driverOptions = []){
		if(!$this->searchConditions){
			return parent::prepare($statement, $driverOptions);
		}
		
		return $this->injectSearchIntoStatement($statement, $driverOptions);
	}
	
	/**
	* Query
	*/
	public function query($statement){
		if(!$this->searchConditions){
			return parent::query($statement);
		}
		
		$query = $this->injectSearchIntoStatement($statement);
		$query->execute();
		return $query;
	}
	
	/**
	* Inject Search Into Statement
	*
	* Calls the driver's search method, and injects its result into search placeholder.
	*/
	private function injectSearchIntoStatement($statement, $driverOptions = []): \PDOStatement{
		$driver = new Driver();
		$placeholders = [];
		
		$search = $driver->search($this->searchConditions['conditions'][0], $this->searchConditions['conditions'][1]);
		$regex = '&:\b'.$this->searchConditions['placeholder'].'\b&';
		
		$statement = preg_replace($regex, $search['whereClause'], $statement);
		
		$query = parent::prepare($statement, $driverOptions);
		foreach($search['placeholders'] as $placeholder => $value){
			$query->bindValue($placeholder, $value);
		}
		
		unset($this->searchConditions);
		return $query;
	}
}
