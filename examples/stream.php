<?php

header('Content-type: text/html; charset=utf-8');

error_reporting(-1);
ini_set('display_errors', 1);

use League\Csv\Reader;
use League\Csv\Writer;
use lib\FilterTranscode;

require '../vendor/autoload.php';
require 'lib/FilterTranscode.php';

//BETWEEN fetch* call you CAN update/remove/add stream filter

stream_filter_register(FilterTranscode::FILTER_NAME."*", "\lib\FilterTranscode");
$reader = new Reader('data/prenoms.csv');
$reader->addStreamFilter(FilterTranscode::FILTER_NAME."iso-8859-1:utf-8");
$reader->addStreamFilter('string.toupper');
$reader->addStreamFilter('string.rot13');
$reader->setDelimiter(';');
$reader->setOffset(6);
$reader->setLimit(3);
$res = $reader->fetchAssoc(['Prenom', 'Occurences', 'Sexe', 'Annee']);
/*
the data is :
 - transcoded by the Stream Filter from ISO-8859-1 to UTF-8
 - uppercased
 - rot13 transform
*/
var_dump($res);
$reader->removeStreamFilter('string.toupper');
$reader->setOffset(6);
$reader->setLimit(3);
$res = $reader->fetchAssoc(['Prenom', 'Occurences', 'Sexe', 'Annee']);
/*
the data is :
 - transcoded by the Stream Filter from ISO-8859-1 to UTF-8
 - rot13 transform
*/
var_dump($res);

// because of the limited support for stream filters with the SplFileObject

// BETWEEN insert* call you CAN NOT UPDATE stream filters
// You must instantiate a new Class for each changes
touch('/tmp/test.csv');
$writer = new Writer(new SplFileInfo('/tmp/test.csv'), 'w');
$writer->addStreamFilter('string.toupper');
$writer->insertOne('je,suis,toto,le,héros');
$writer->addStreamFilter('string.rot13');
$writer->insertOne('je,suis,toto,le,héros');
/*
the data is :
 - uppercased only
*/
$writer = new Writer('/tmp/test.csv', 'a+');
$writer->addStreamFilter('string.toupper');
$writer->addStreamFilter(FilterTranscode::FILTER_NAME."iso-8859-1:utf-8");
$writer->addStreamFilter('string.rot13');
$writer->removeStreamFilter(FilterTranscode::FILTER_NAME."iso-8859-1:utf-8");
$writer->insertOne('je,suis,toto,le,héros');
/*
the data is :
 - uppercased
 - rot13 transform
*/
$reader = new Reader('/tmp/test.csv');
$reader->setFlags(SplFileObject::READ_AHEAD|SplFileObject::SKIP_EMPTY);
var_dump($reader->fetchAll());
