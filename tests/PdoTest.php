<?php
namespace FHMJ\PdoFlexibleSearch;

use PHPUnit\Framework\TestCase;

class PdoTest extends TestCase{
	/*******************************
	* SETUP
	*******************************/
	private $pdo = null;
	
	/**
	* Setup
	*/
	public function setup(){
		$this->pdo = new Pdo('sqlite::memory:', '', '', [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
		]);
		$this->pdo->query('
			CREATE TABLE test_table(
				id INT,
				name VARCHAR(255)
			)
		');
		
		$this->pdo->query('
			INSERT INTO test_table VALUES
			(1, "Entry 1"),
			(2, "Entry 2"),
			(3, "Entry 3"),
			(4, "Entry 4"),
			(5, "Entry 5")
		');
	}
	
	
	/*******************************
	* TESTS
	*******************************/
	/**
	* Test It Extends PHP PDO
	*/
	public function testItExtendsPhpPdo(){
		$this->assertInstanceOf(\PDO::class, $this->pdo);
	}
	
	/**
	* Test It Can Perform Default Execute
	*/
	public function testItCanPerformDefaultExecute(){
		$this->assertSame(1, $this->pdo->exec('INSERT INTO test_table VALUES(6, "Entry 6")'));
	}
	
	/**
	* Test It Can Perform Default Prepare
	*/
	public function testItCanPerformDefaultPrepare(){
		$statement = '
			SELECT id
			FROM test_table
			WHERE name = :name
		';
		$query = $this->pdo->prepare($statement);
		$query->bindValue('name', 'Entry 2');
		$query->execute();
		
		$this->assertSame([['id' => '2']], $query->fetchAll());
	}
	
	/**
	* Test It Can Perform Default Query
	*/
	public function testItCanPerformDefaultQuery(){
		$statement = '
			SELECT id
			FROM test_table
			WHERE name = "Entry 2"
		';
		$query = $this->pdo->query($statement);
		$this->assertSame([['id' => '2']], $query->fetchAll());
	}
	
	/**
	* Test It Can Perform Search With Execute
	*/
	public function testItCanPerformSearchWithExecute(){
		$statement = 'UPDATE test_table SET name = "Test" WHERE :myPlaceholder';
		$this->assertSame(2, $this->pdo->search(':myPlaceholder', ['BT/id' => [3, 4]])->exec($statement));
	}
	
	/**
	* Test It Can Perform Search With Prepare
	*/
	public function testItCanPerformSearchWithPrepare(){
		$statement = '
			SELECT id
			FROM test_table
			WHERE :myPlaceholder
		';
		$query = $this->pdo->search(':myPlaceholder', ['BT/id' => [3, 4]])->prepare($statement);
		$query->execute();
		$this->assertSame([['id' => '3'], ['id' => '4']], $query->fetchAll());
	}
	
	/**
	* Test It Can Perform Search With Query
	*/
	public function testItCanPerformSearchWithQuery(){
		$statement = '
			SELECT id
			FROM test_table
			WHERE :myPlaceholder
		';
		$query = $this->pdo->search(':myPlaceholder', ['BT/id' => [3, 4]])->query($statement);
		$this->assertSame([['id' => '3'], ['id' => '4']], $query->fetchAll());
	}
	
	/**
	* Test It Can Make Use Of All Search Tokens
	*/
	public function testItCanMakeUseOfAllSearchTokens(){
		$statement = '
			SELECT id
			FROM test_table
			WHERE :myPlaceholder
		';
		
		$query = $this->pdo->search(':myPlaceholder', ['E/id' => 3])->query($statement);
		$this->assertSame([['id' => '3']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['!E/id' => 3])->query($statement);
		$this->assertSame([['id' => '1'], ['id' => '2'], ['id' => '4'], ['id' => '5']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['GT/id' => 3])->query($statement);
		$this->assertSame([['id' => '4'], ['id' => '5']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['!GT/id' => 3])->query($statement);
		$this->assertSame([['id' => '1'], ['id' => '2'], ['id' => '3']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['GTE/id' => 3])->query($statement);
		$this->assertSame([['id' => '3'], ['id' => '4'], ['id' => '5']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['!GTE/id' => 3])->query($statement);
		$this->assertSame([['id' => '1'], ['id' => '2']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['LT/id' => 3])->query($statement);
		$this->assertSame([['id' => '1'], ['id' => '2']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['!LT/id' => 3])->query($statement);
		$this->assertSame([['id' => '3'], ['id' => '4'], ['id' => '5']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['LTE/id' => 3])->query($statement);
		$this->assertSame([['id' => '1'], ['id' => '2'], ['id' => '3']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['!LTE/id' => 3])->query($statement);
		$this->assertSame([['id' => '4'], ['id' => '5']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['id' => 3, 'L/name' => '%try%'])->query($statement);
		$this->assertSame([['id' => '3']], $query->fetchAll());
		
		$query = $this->pdo->search(':myPlaceholder', ['id' => 3, '!L/name' => '%Eentry%'])->query($statement);
		$this->assertSame([['id' => '3']], $query->fetchAll());
	}
}
