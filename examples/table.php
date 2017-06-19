<?php

error_reporting(-1);
ini_set('display_errors', 1);

use League\Csv\Reader;

require '../vendor/autoload.php';

$inputCsv = Reader::createFromPath('data/prenoms.csv');
$inputCsv->setDelimiter(';');
$inputCsv->setEncodingFrom("iso-8859-15");
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="<?=$inputCsv->getEncodingFrom()?>">
    <title>Using the toHTML() method</title>
    <link rel="stylesheet" href="example.css">
</head>
<body>
<?=$inputCsv->toHTML('table-csv-data with-header');?>
</body>
</html>
