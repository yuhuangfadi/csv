<?php
/**
* Bakame.csv - A lightweight CSV Coder/Decoder library
*
* @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
* @copyright 2014 Ignace Nyamagana Butera
* @link https://github.com/nyamsprod/Bakame.csv
* @license http://opensource.org/licenses/MIT
* @version 4.0.0
* @package Bakame.csv
*
* MIT LICENSE
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
* LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
* WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
namespace Bakame\Csv;

use InvalidArgumentException;
use Traversable;

/**
 *  A Writer to ease CSV creation in PHP 5.4+
 *
 * @package Bakame.csv
 * @since  4.0.0
 *
 */
class Writer extends Csv
{
    /**
     * {@inheritdoc}
     */
    protected $mode = 'w';

    /**
     * Add a new CSV row to the generated CSV
     *
     * @param string|array|object $row the line to be added to the CSV
     *
     * @return integer
     *
     * @throws InvalidArgumentException If the given row format is invalid
     */
    public function append($row)
    {
        if (self::isValidString($row)) {
            $row = str_getcsv((string) $row, $this->delimiter, $this->enclosure, $this->escape);
        }
        if (! is_array($row)) {
            throw new InvalidArgumentException(
                'the row provided must be an array of a valid string that can be converted into an array'
            );
        }
        $check = array_filter($row, function ($value) {
            return $this->isValidString($value);
        });
        if (count($check) == count($row)) {
            $this->csv->fputcsv($row);

            return $this;
        }
        throw new InvalidArgumentException(
            'the provided data can not be transform into a single CSV data row'
        );
    }

    /**
     * Add multiple lines to the CSV your are generating
     *
     * @param array|\Traversable $rows the lines to be added to the CSV
     *
     * @return self
     *
     * @throws InvalidArgumentException If the given rows format is invalid
     */
    public function save($rows)
    {
        if (! is_array($rows) && ! $rows instanceof Traversable) {
            throw new InvalidArgumentException(
                'the provided data must be an array OR a \Traversable object'
            );
        }

        foreach ($rows as $row) {
            $this->append($row);
        }

        return $this;
    }

    /**
     * Return a Reader CSV class for the current Writer
     *
     * @return \Bakame\Csv\Reader
     */
    public function getReader()
    {
        return new Reader(
            $this->csv,
            $this->delimiter,
            $this->enclosure,
            $this->escape,
            $this->flags
        );
    }
}
