---
layout: default
title: Converting Csv records character encoding
---

# Charset conversion

~~~php
<?php

class CharsetConverter extends php_user_filter
{
    const STREAM_FILTERNAME = 'convert.league.csv';

    public function __invoke(array $record): array
    public static function addTo(AbstractCsv $csv, string $input_encoding, string $output_encoding)
    public function convert(iterable $records): Iterator
    public function inputEncoding(string $input_encoding): self
    public function outputEncoding(string $output_encoding): self
}
~~~

The `CharsetConverter` class converts your CSV records using the `mbstring` extension and its [supported character encodings](http://php.net/manual/en/mbstring.supported-encodings.php).

## Settings

~~~php
<?php

public CharsetConverter::inputEncoding(string $input_encoding): self
public CharsetConverter::outputEncoding(string $output_encoding): self
~~~

The `inputEncoding` and `outputEncoding` methods sets the object encoding properties. By default, the input encoding and the output encoding are set to `UTF-8`.

When building a `CharsetConverter` object, the methods do not need to be called in any particular order, and may be called multiple times. Because the `CharsetConverter` is immutable, each time its setter methods are called they return a new object without modifying the current one.

<p class="message-warning">If the submitted charset is not supported by the <code>mbstring</code> extension an <code>OutOfRangeException</code> will be thrown.</p>

## Conversion

~~~php
<?php

public CharsetConverter::convert(iterable $records): Iterator
~~~

`CharsetConverter::convert` converts the collection of records charset encoding.

~~~php
<?php

use League\Csv\CharsetConverter;

$csv = new SplFileObject('/path/to/french.csv', 'r');
$csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

$encoder = (new CharsetConverter())->inputEncoding('iso-8859-15');
$records = $encoder->convert($csv);
~~~

The resulting data is converted from `iso-8859-15` to the default `UTF-8` since `outputEncoding` was not called.


## CharsetConverter as a Writer formatter

~~~php
<?php

public CharsetConverter::__invoke(array $record): array
~~~

Using the `CharsetConverter::__invoke` method, you can register a `CharsetConverter` object as a record formatter using [Writer::addFormatter](/9.0/writer/#record-formatter) method.

~~~php
<?php

use League\Csv\CharsetConverter;
use League\Csv\Writer;

$encoder = (new CharsetConverter())
    ->inputEncoding('utf-8')
    ->outputEncoding('iso-8859-15')
;

$writer = Writer::createFromPath('/path/to/your/csv/file.csv');
$writer->addFormatter($encoder);

$writer->insertOne(["foo", "bébé", "jouet"]);
//all 'utf-8' caracters are now automatically encoded into 'iso-8859-15' charset
~~~

## CharsetConverter as a PHP stream filter

~~~php
<?php

public static CharsetConverter::addTo(AbstractCSV $csv, string $input_encoding, string $output_encoding)
~~~

If your CSV object supports PHP stream filters then you can use the `CharsetConverter` class as a PHP stream filter and use the library [stream filtering mechanism](/9.0/connections/filters/) instead.

The `CharsetConverter::addTo` static method:

- registers the `CharsetConverter` class under the following generic filtername `convert.league.csv.*` if it is not registered yet.
- configures the stream filter using the supplied parameters.
- adds the configured stream filter to the submitted CSV object;

~~~php
<?php

use League\Csv\CharsetConverter;
use League\Csv\Writer;

$writer = Writer::createFromPath('/path/to/your/csv/file.csv');
CharsetConverter::addTo($writer, 'utf8', 'iso-8859-15');

$writer->insertOne(["foo", "bébé", "jouet"]);
//all 'utf-8' caracters are now automatically encoded into 'iso-8859-15' charset
~~~

<p class="message-info">If your system supports the <code>iconv</code> extension you should use PHP's built in iconv stream filters instead for better performance.</p>
