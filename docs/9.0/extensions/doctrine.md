---
layout: default
title: Using CSV with Doctrine packages
---

# Doctrine specific adapter

This extension package contains:

- a class to convert `League\Csv\Reader` instances into [Doctrine Collections](https://www.doctrine-project.org/projects/collections.html) objects.
- a class to enable using [Doctrine Collections powerful Expression API](https://www.doctrine-project.org/projects/doctrine-collections/en/latest/expressions.html) on League Csv TabularReader objects.

```php
<?php

use Doctrine\Common\Collections\Criteria;
use League\Csv\Doctrine as CsvDoctrine;
use League\Csv\Reader;

$csv = Reader::createFromPath('/path/to/my/file.csv');
$csv->setHeaderOffset(0);
$csv->setDelimiter(';');

$criteria = Criteria::create()
    ->andWhere(Criteria::expr()->eq('prenom', 'Adam'))
    ->orderBy( [ 'annee' => 'ASC', 'foo' => 'desc', ] )
    ->setFirstResult(0)
    ->setMaxResults(10)
;

//you can do

$resultset = CsvDoctrine\CriteriaConverter::convert($criteria)->process($csv);
$result = new CsvDoctrine\RecordCollection($resultset);

//or

$collection = new CsvDoctrine\RecordCollection($csv);
$result = $collection->matching($criteria);
```

## System Requirements

- **league/csv >= 9.6**
- **doctrine/collection >= 1.6.0**

but the latest stable version of each dependency is recommended.

## Installation

```bash
composer require league/csv-doctrine
```

## Usage

### Converting a `League\Csv\Reader` into a Doctrine Collection object

```php
<?php

use League\Csv\Doctrine\RecordCollection;
use League\Csv\Reader;

$csv = Reader::createFromPath('/path/to/my/file.csv');
$csv->setHeaderOffset(0);
$csv->setDelimiter(';');

$collection = new RecordCollection($csv);
```

### Converting a `League\Csv\ResultSet` into a Doctrine Collection object

```php
<?php

$csv = Reader::createFromPath('/path/to/my/file.csv');
$csv->setHeaderOffset(0);
$csv->setDelimiter(';');

$stmt = Statement::create()
    ->where(fn (array $row): bool => isset($row['email']) && str_contains($row['email'], '@github.com'));

$collection = new RecordCollection($stmt->process($csv));
```

### Using Doctrine Criteria to filter a `League\Csv\Reader` object

```php
<?php

use Doctrine\Common\Collections\Criteria;
use League\Csv\Doctrine\CriteriaConverter;
use League\Csv\Reader;

$csv = Reader::createFromPath('/path/to/my/file.csv');
$csv->setHeaderOffset(0);
$csv->setDelimiter(';');

$criteria = Criteria::create()
    ->andWhere(Criteria::expr()->eq('name', 'Adam'))
    ->orderBy(['years', 'ASC'])
    ->setFirstResult(0)
    ->setMaxResults(10)
;

$stmt = CriteriaConverter::convert($criteria);
$resultset = $stmt->process($csv);
```

### CriteriaConverter advanced usages

```php
<?php

use Doctrine\Common\Collections\Criteria;
use League\Csv\Statement;

public static CriteriaConverter::convert(Criteria $criteria, Statement $stmt = null): Statement
public static CriteriaConverter::addWhere(Criteria $criteria, Statement $stmt = null): Statement
public static CriteriaConverter::addOrderBy(Criteria $criteria, Statement $stmt = null): Statement
public static CriteriaConverter::addInterval(Criteria $criteria, Statement $stmt = null): Statement
```

- `CriteriaConverter::convert` converts the `Criteria` object into a `Statement` object.
- `CriteriaConverter::addWhere` adds the `Criteria::getWhereExpression` filters to the submitted `Statement` object.
- `CriteriaConverter::addOrderBy` adds the `Criteria::getOrderings` filters to the submitted `Statement` object.
- `CriteriaConverter::addInterval` adds the `Criteria::getFirstResult` and `Criteria::getMaxResults` filters to the submitted `Statement` object.

**WARNING: While the `Criteria` object is mutable the `Statement` object is immutable. All returned `Statement` objects are new instances**
