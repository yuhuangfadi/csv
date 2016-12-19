<?php

error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('Europe/Brussels');

use Bakame\Csv\Reader;

require '../vendor/autoload.php';

$inputCsv = new Reader('data/prenoms.csv');
$inputCsv->setDelimiter(';');
$inputCsv->setEncoding("iso-8859-15");
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="<?=$inputCsv->getEncoding()?>">
    <title>Example 2</title>
</head>
<body>
<?=$inputCsv->toHTML('table-csv-data');?>
</body>
</html>
