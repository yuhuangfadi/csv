---
layout: default
title: BOM sequence detection and addition
---

# BOM sequences

## Detecting the BOM sequence

~~~php
<?php

const BOM::UTF8 = "\xEF\xBB\xBF";
const BOM::UTF16_BE = "\xFE\xFF";
const BOM::UTF16_LE = "\xFF\xFE";
const BOM::UTF32_BE = "\x00\x00\xFE\xFF";
const BOM::UTF32_LE = "\xFF\xFE\x00\x00";
~~~

To improve interoperability with programs interacting with CSV, the package now provide an interface `BOM` to help you detect set the appropriate <abbr title="Byte Order Mark">BOM</abbr> sequence.

### Constants

To ease BOM sequence manipulation, the `BOM` class provides the following constants :

* `BOM::UTF8` : `UTF-8` `BOM`;
* `BOM::UTF16_BE` : `UTF-16` `BOM` with Big-Endian;
* `BOM::UTF16_LE` : `UTF-16` `BOM` with Little-Endian;
* `BOM::UTF32_BE` : `UTF-32` `BOM` with Big-Endian;
* `BOM::UTF32_LE` : `UTF-32` `BOM` with Little-Endian;

### bom_match

~~~php
<?php

function bom_match(string $str): string
~~~

The `League\Csv\bom_match` function expects a string and returns the BOM sequence found at its start or an empty string otherwise.

~~~php
<?php

use League\Csv\BOM;
use function League\Csv\bom_match;

bom_match('hello world!'); //returns ''
bom_match(BOM::UTF8.'hello world!'); //returns '\xEF\xBB\xBF'
bom_match('hello world!'.BOM::UTF16_BE); //returns ''
~~~

## Managing CSV documents BOM sequence

### Detecting the BOM sequence

~~~php
<?php

public AbstractCsv::getInputBOM(void): string
~~~

The CSV document current BOM character is detected using the `getInputBOM` method. This method returns the currently used BOM character or an empty string if none is found or recognized. The detection is done using the `bom_match` function.

~~~php
<?php

use League\Csv\Writer;

$csv = Writer::createFromPath('/path/to/file.csv');
$bom = $csv->getInputBOM();
~~~

### Setting the outputted BOM sequence

~~~php
<?php

public AbstractCsv::setOutputBOM(string $sequence): self
public AbstractCsv::getOutputBOM(void): string
~~~

- `setOutputBOM`: sets the outputting BOM you want your CSV to be associated with.
- `getOutputBOM`: get the outputting BOM you want your CSV to be associated with.

~~~php
<?php

use League\Csv\BOM;
use League\Csv\Reader;

$csv = Reader::createFromPath('/path/to/file.csv');
$csv->setOutputBOM(BOM::UTF8);
$bom = $csv->getOutputBOM(); //returns "\xEF\xBB\xBF"
~~~

<p class="message-info">The default output <code>BOM</code> character is set to an empty string.</p>
<p class="message-warning">The output BOM sequence is <strong>never</strong> saved to the CSV document.</p>

