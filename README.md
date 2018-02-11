# PDO Flexible Search - Flexible SQL search queries in PHP

Pdo Flexible Search (fancy shortname pending..) allows you to use an array
to setup database searches through PDO.

Search parameters can be defined through a method call, or by making use of 
method chaining.

## Installation
This library is on packagist, and the latest version can be installed with composer:
```bash
$ composer require fhmj/pdo-flexible-search
```

## Basic Usage
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

// define search parameters
$search = [
    'BT/age' => [23, 41],
    'city' => 'Vejle'
];

// perform query
$query = $pdo->search('mySearch', $search)->query($sql);
$query->fetchAll(\PDO::FETCH_ASSOC);

/*
Generated query (example only, actual code WILL use placeholders)
    SELECT *
    FROM customer
    WHERE age BETWEEN 23 AND 41
    AND city = Vejle
    ORDER BY id ASC
*/
```

## In Depth Usage
For full documentation, please refer to the [examples document.](examples.md)

## Feedback
Found a bug? Have a suggestion? Feel like you can tackle an issue?

Feel free to contact me on frankhjorth@gmail.com or write an [issue](https://github.com/fhmj/pdo-flexible-search/issues)
