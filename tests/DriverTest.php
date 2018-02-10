<?php
namespace FHMJ\PdoFlexibleSearch;

use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase{
	/*******************************
	* SETUP
	*******************************/
	private $driver = null;
	
	/**
	* Setup
	*/
	public function setup(){
		$this->driver = new Driver();
	}
	
	
	/*******************************
	* TESTS
	*******************************/
	/**
	* Test It Can Generate Operator With Single Value
	*/
	public function testItCanGenerateOperatorWithSingleValue(){
		$this->assertSame(
			'col = val',
			$this->driver->operator('col', ['val'], '=')
		);
	}
	
	/**
	* Test It Can Generate Operator With Multiple Values
	*/
	public function testItCanGenerateOperatorWithMultipleValues(){
		$this->assertSame(
			'col = val OR col = val2',
			$this->driver->operator('col', ['val', 'val2'], '=')
		);
	}
	
	/**
	* Test Generating Operator With Empty Column Throws
	*/
	public function testGenerationOperatorWithEmptyColumnThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('column must not be empty');
		$this->driver->operator('', ['val'], '=');
	}
	
	/**
	* Test Generating Operator With Empty Value Throws
	*/
	public function testGenerationOperatorWithEmptyValueThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('value must not be empty');
		$this->driver->operator('col', [''], '=');
	}
	
	/**
	* Test Generating Operator With Empty Value Space Throws
	*/
	public function testGenerationOperatorWithEmptyValueSpaceThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('value must not be empty');
		$this->driver->operator('col', [' '], '=');
	}
	
	/**
	* Test Generating Operator With Empty Value Parameter Throws
	*/
	public function testGenerationOperatorWithEmptyValueParameterThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('operator generation requires at least one value');
		$this->driver->operator('col', [], '=');
	}
	
	/**
	* Test It Can Generate Operator With Zero Value
	*/
	public function testItCanGenerateOperatorWithZeroValue(){
		$this->assertSame(
			'col = 0',
			$this->driver->operator('col', [0], '=')
		);
	}
	
	/**
	* Test It Can Generate Operator With Null Value
	*/
	public function testItCanGenerateOperatorWithNullValue(){
		$this->assertSame(
			'col = NULL',
			$this->driver->operator('col', [null], '=')
		);
	}
	
	/**
	* Test It Can Generate Operator With Bool Value
	*/
	public function testItCanGenerateOperatorWithBoolValue(){
		$this->assertSame(
			'col = 0 OR col = 1',
			$this->driver->operator('col', [false, true], '=')
		);
	}
	
	/**
	* Test Generating Operator With Array Value Throws
	*/
	public function testGenerationOperatorWithArrayValueThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('value must be NULL or a scalar value');
		$this->driver->operator('col', [['val']], '=');
	}
	
	/**
	* Test Generating Operator With Object Value Throws
	*/
	public function testGenerationOperatorWithObjectValueThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('value must be NULL or a scalar value');
		$this->driver->operator('col', [[new \stdClass()]], '=');
	}
	
	/**
	* Test It Can Parse Column String
	*/
	public function testItCanParseColumnString(){
		$this->assertSame(
			[
				['token' => '', 'column' => 'col']
			],
			$this->driver->parseColumnString('col')
		);
	}
	
	/**
	* Test It Can Parse Column String With Token
	*/
	public function testItCanParseColumnStringWithToken(){
		$this->assertSame(
			[
				['token' => 'E', 'column' => 'col']
			],
			$this->driver->parseColumnString('E/col')
		);
	}
	
	/**
	* Test It Can Parse Column String With Multiple Columns
	*/
	public function testItCanParseColumnStringWithMultipleColumns(){
		$this->assertSame(
			[
				['token' => '', 'column' => 'col'],
				['token' => '', 'column' => 'col2'],
				['token' => '', 'column' => 'col3']
			],
			$this->driver->parseColumnString('col;col2;col3')
		);
	}
	
	/**
	* Test It Can Parse Column String With Multiple Columns And Tokens
	*/
	public function testItCanParseColumnStringWithMultipleColumnsAndTokens(){
		$this->assertSame(
			[
				['token' => 'E', 'column' => 'col'],
				['token' => '', 'column' => 'col2'],
				['token' => '!E', 'column' => 'col3']
			],
			$this->driver->parseColumnString('E/col;col2;!E/col3')
		);
	}
	
	/**
	* Test It Can Merge Column Data Back To String
	*/
	public function testItCanMergeColumnDataBackToString(){
		$this->assertSame(
			'E/col;col2;!E/col3',
			$this->driver->mergeColumnData([
				['token' => 'E', 'column' => 'col'],
				['token' => '', 'column' => 'col2'],
				['token' => '!E', 'column' => 'col3']
			])
		);
	}
	
	/**
	* Test It Can Generate Search With And Conditions
	*/
	public function testItCanGenerateSearchWithAndConditions(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val',
					'pdoFlexibleSearch1' => 'val2',
					'pdoFlexibleSearch2' => 'val3'
				],
				'whereClause' =>
					'(col = :pdoFlexibleSearch0 OR col = :pdoFlexibleSearch1)'.PHP_EOL
					.'AND (col2 = :pdoFlexibleSearch2)'
			],
			$this->driver->search(['E/col' => ['val', 'val2'], 'E/col2' => 'val3'])
		);
	}
	
	/**
	* Test It Can Generate Search With Or Conditions
	*/
	public function testItCanGenerateSearchWithOrConditions(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val',
					'pdoFlexibleSearch1' => 'val2',
					'pdoFlexibleSearch2' => 'val3'
				],
				'whereClause' =>
					'(col = :pdoFlexibleSearch0 OR col = :pdoFlexibleSearch1)'.PHP_EOL
					.'OR (col2 = :pdoFlexibleSearch2)'
			],
			$this->driver->search([], ['E/col' => ['val', 'val2'], 'E/col2' => 'val3'])
		);
	}
	
	/**
	* Test It Can Generate Search With Both Condition Types
	*/
	public function testItCanGenerateSearchWithBothConditionTypes(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val',
					'pdoFlexibleSearch1' => 'val2',
					'pdoFlexibleSearch2' => 'val3',
					'pdoFlexibleSearch3' => 'val4',
					'pdoFlexibleSearch4' => 'val5',
					'pdoFlexibleSearch5' => 'val6'
				],
				'whereClause' =>
					'(col = :pdoFlexibleSearch0 OR col = :pdoFlexibleSearch1)'.PHP_EOL
					.'AND (col2 = :pdoFlexibleSearch2)'.PHP_EOL
					.'AND ( (col3 = :pdoFlexibleSearch3 OR col3 = :pdoFlexibleSearch4)'.PHP_EOL
					.'OR (col4 = :pdoFlexibleSearch5) )'
			],
			$this->driver->search(
				['E/col' => ['val', 'val2'], 'E/col2' => 'val3'],
				['E/col3' => ['val4', 'val5'], 'E/col4' => 'val6']
			)
		);
	}
	
	/**
	* Test It Can Generate Search With Default Token
	*/
	public function testItCanGenerateSearchWithDefaultToken(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val'
				],
				'whereClause' => '(col = :pdoFlexibleSearch0)'
			],
			$this->driver->search(['col' => 'val'])
		);
	}
	
	/**
	* Test Generate Search Can Flatten Multi Dimensional Array Values
	*/
	public function testGenerateSearchCanFlattenMultiDimensionalArrayValues(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val',
					'pdoFlexibleSearch1' => 'val2',
					'pdoFlexibleSearch2' => 'val3'
				],
				'whereClause' => '(col = :pdoFlexibleSearch0 OR col = :pdoFlexibleSearch1 OR col = :pdoFlexibleSearch2)'
			],
			$this->driver->search(['col' => ['val', ['val2', 'val3']]])
		);
	}
	
	/**
	* Test It Can Generate Search With Processed Token
	*/
	public function testItCanGenerateSearchWithProcessedToken(){
		$this->assertSame(
			[
				'placeholders' => [
					'pdoFlexibleSearch0' => 'val',
					'pdoFlexibleSearch1' => 'val2'
				],
				'whereClause' => '(col BETWEEN :pdoFlexibleSearch0 AND :pdoFlexibleSearch1)'
			],
			$this->driver->search(['BT/col' => ['val', 'val2']])
		);
	}
	
	/**
	* Test Generate Search With Unknown Token Throws
	*/
	public function testGenerateSearchWithUnknownTokenThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('use of unknown search token `UN`');
		$this->driver->search(['UN/col' => 'val']);
	}
	
	/**
	* Test Generate Search With No Conditions Throws
	*/
	public function testGenerateSearchWithNoConditionsThrows(){
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('search requires at least one condition');
		$this->driver->search([]);
	}
}
