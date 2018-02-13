# PDO Flexible Search - Flexible SQL search queries in PHP

Pdo Flexible Search (fancy shortname pending..) allows you to use an array
to setup database searches through PDO.

The purpose of this tool is to make it easy to setup a flexible search, where
the conditions are defined runtime.

Search parameters are defined as an array, where the **key** corresponds to the 
database column, and the **value** to the search condition.

## Installation

This library is on packagist, and the latest version can be installed with composer:

```bash
$ composer require fhmj/pdo-flexible-search
```

## Basic Usage
```php
Pdo::search(string $searchKey, array $andConditions, array $orConditions = []): Pdo;
# searchKey        The placeholder that will be replaced by generated sql  
# andConditions    A set of conditions that all have to be met  
# orConditions     A set of conditions where only one of them have to be met  
```

```php
// instantiate db connection
$pdo = new FHMJ\PdoFlexibleSearch\Pdo('sqlite::memory:');

// define sql
$sql = '
    SELECT *
    FROM customer
    WHERE :mySearch
    ORDER BY id ASC
';

// define "and conditions"
$andConditions = [
    'BT/age' => [23, 41],
    'city' => 'Vejle'
];

// define "or conditions"
$orConditions = [
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// perform query
$query = $pdo->search('mySearch', $andConditions, $orConditions)->query($sql);
$query->fetchAll(\PDO::FETCH_ASSOC);

/*
Generated query (example only, actual code WILL use placeholders)
    SELECT *
    FROM customer
    WHERE age BETWEEN 23 AND 41
    AND city = Vejle
	AND (
		first_name = John
		OR last_name = Doe
	)
    ORDER BY id ASC
*/
```

## Sql Comparison Operators
Sql operators are supported in the form of **tokens**. This is done by adding
the token to the column as a prefix, separated by a forward-slash (/).

The format can be defined as **(TOKEN/)?COLUMN**.

Here is a list of tokens, and their corresponding operators:

```
 E    =    (equals)
!E   !=    (not equals)
GT    >    (greater than)
GTE   >=   (greater than or equal)
LT    <    (less than)
LTE   <=   (less than or equal)
L     LIKE (sql LIKE comparison)
IS    IS   (sql IS comparison)

 BT   BETWEEN X AND Y        (value would have to be an array)
!BT   NOT BETWEEN X AND Y    (value would have to be an array)
```

### Comparing multiple columns to a set of values
In case you need to compare multiple columns to the same set of values,
the **key** can contain multiple columns separated by a semi-colon (;).

```php
['LT/colA;GT/colB' => 7] // and conditions
'(colA < 7 OR colB > 7)' // sql result
```

## Feedback
Found a bug? Have a suggestion? Feel like you can tackle an issue?

Contact me on frankhjorth@gmail.com or write an [issue](https://github.com/fhmj/pdo-flexible-search/issues)
