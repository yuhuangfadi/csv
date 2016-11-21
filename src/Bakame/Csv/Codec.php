<?php
/**
* Bakame.csv - A lightweight CSV Coder/Decoder library
*
* @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
* @copyright 2013 Ignace Nyamagana Butera
* @link https://github.com/nyamsprod/Bakame.csv
* @license http://opensource.org/licenses/MIT
* @version 2.0.0
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

use SplFileInfo;
use SplFileObject;
use SplTempFileObject;
use Traversable;
use InvalidArgumentException;

/**
 *  A simple Coder/Decoder to ease CSV management in PHP 5.4+
 *
 * @package Bakame.csv
 */
class Codec
{
    /**
     * the field delimiter (one character only)
     *
     * @var string
     */
    private $delimiter = ',';

    /**
     * the field enclosure character (one character only)
     *
     * @var string
     */
    private $enclosure = '"';

    /**
     * the field escape character (one character only)
     * @var string
     */
    private $escape = '\\';

    /**
     * The constructor
     *
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct($delimiter = ',', $enclosure = '"', $escape = "\\")
    {
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setEscape($escape);
    }

    /**
     * set the field delimeter
     *
     * @param string $delimiter
     *
     * @return self
     *
     * @throws \InvalidArgumentException If $delimeter is not a single character
     */
    public function setDelimiter($delimiter = ',')
    {
        if (1 != mb_strlen($delimiter)) {
            throw new InvalidArgumentException('The delimiter must be a single character');
        }
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * return the current field delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * set the field enclosure
     *
     * @param string $enclosure
     *
     * @return self
     *
     * @throws \InvalidArgumentException If $enclosure is not a single character
     */
    public function setEnclosure($enclosure = '"')
    {
        if (1 != mb_strlen($enclosure)) {
            throw new InvalidArgumentException('The enclosure must be a single character');
        }
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * return the current field enclosure
     *
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * set the field escape character
     *
     * @param string $escape
     *
     * @return self
     *
     * @throws \InvalidArgumentException If $escape is not a single character
     */
    public function setEscape($escape = "\\")
    {
        if (1 != mb_strlen($escape)) {
            throw new InvalidArgumentException('The escape character must be a single character');
        }
        $this->escape = $escape;

        return $this;
    }

    /**
     * return the current field escape character
     *
     * @return string
     */
    public function getEscape()
    {
        return $this->escape;
    }

    /**
     * Load a CSV string
     *
     * @param string $str the csv content string
     *
     * @return \SplTempFileObject
     */
    public function loadString($str)
    {
        $file = new SplTempFileObject();
        $file->fwrite($str);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

        return $file;
    }

    /**
     * Load a CSV File
     *
     * @param string $str the file path
     *
     * @return \SplFileObject
     */
    public function loadFile($path, $mode = 'r')
    {
        return $this->create($path, $mode, ['r', 'r+', 'w+', 'x+', 'a+', 'c+']);
    }

    /**
     * Save the given data into a CSV
     *
     * @param array|\Traversable  $data the data to be saved (Array or Traversable Interface)
     * @param string|\SplFileInfo $path where to save the data (String Path or SplFileInfo Instance)
     * @param string              $mode specifies the type of access you require to the file
     *
     * @return \SplFileObject
     */
    public function save($data, $path, $mode = 'w')
    {
        $file = $this->create($path, $mode, ['r+', 'w', 'w+', 'x', 'x+', 'a', 'a+', 'c', 'c+']);
        $data = $this->formatData($data);
        array_walk($data, function ($row) use ($file) {
            $file->fputcsv($row);
        });

        return $file;
    }

    /**
     * format the data before inclusion into the CSV
     *
     * @param array|\Traversable $traversable the data to be formatted (Array or Traversable Interface)
     *
     * @return array
     *
     * @throws \InvalidArgumentException If $data is not an array or does not implement the \Traversable interface
     */
    private function formatData($traversable)
    {
        if (! is_array($traversable) && ! $traversable instanceof Traversable) {
            throw new InvalidArgumentException(
                'The provided data must be an Array or an object implementing the `Traversable` interface'
            );
        }
        $res = [];
        foreach ($traversable as $row) {
            $res[] = $this->extractRowData($row);
        }

        return $res;
    }

    /**
     * extract and format row field data to be string
     *
     * @param mixed $row the data for One CSV line
     *
     * @return array
     */
    private function extractRowData($row)
    {
        if (is_array($row)) {
            return array_map(function ($value) {
                return (string) $value;
            }, $row);
        }

        return explode($this->delimiter, (string) $row);
    }

    /**
     * Return a new \SplFileObject
     *
     * @param mixed  $path    where to save the data (String or SplFileInfo Instance)
     * @param string $mode    specifies the type of access you require to the file
     * @param array  $include non valid type of access
     *
     * @return \SplFileObject
     *
     * @throws \InvalidArgumentException If the $file is not set
     */
    private function create($path, $mode, array $include = [])
    {
        $include += ['r', 'r+', 'w', 'w+', 'x', 'x+', 'a', 'a+', 'c', 'c+'];
        $mode = $this->filterMode($mode, $include);
        if ($path instanceof SplFileInfo) {
            $file = $path->openFile($mode);
            $file->setFlags(SplFileObject::READ_CSV);
            $file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

            return $file;
        } elseif (is_string($path)) {
            $file = new SplFileObject($path, $mode);
            $file->setFlags(SplFileObject::READ_CSV);
            $file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

            return $file;
        }
        throw new InvalidArgumentException('$path must be a `SplFileInfo` object or a valid file path.');
    }

    /**
     * validate the type of access you require for a given file
     *
     * @param string $mode    specifies the type of access you require to the file
     * @param array  $include valid type of access
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the $mode is invalid
     */
    private function filterMode($mode, array $include)
    {
        $mode = strtolower($mode);
        if (! in_array($mode, $include)) {
            throw new InvalidArgumentException(
                'Invalid `$mode` value. Available values are : "'.implode('", "', $include).'"'
            );
        }

        return $mode;
    }
}
