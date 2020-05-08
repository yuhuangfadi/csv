<?php
/**
* This file is part of the League.csv library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/thephpleague/csv/
* @version 9.0.0
* @package League.csv
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace League\Csv;

use CallbackFilterIterator;
use Countable;
use Generator;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use League\Csv\Exception\OutOfRangeException;
use League\Csv\Exception\RuntimeException;
use LimitIterator;

/**
 * Represents the result set of a {@link Reader} processed by a {@link Statement}
 *
 * @package League.csv
 * @since   9.0.0
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 */
class ResultSet implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The CSV records collection
     *
     * @var Iterator
     */
    protected $iterator;

    /**
     * The CSV records collection column names
     *
     * @var array
     */
    protected $column_names = [];

    /**
     * Tell whether the CSV records offset must be kept on output
     *
     * @var bool
     */
    protected $preserve_offset = false;

    /**
     * New instance
     *
     * @param Iterator $iterator     a CSV records collection iterator
     * @param array    $column_names the associated collection column names
     */
    public function __construct(Iterator $iterator, array $column_names)
    {
        $this->iterator = $iterator;
        $this->column_names = $column_names;
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        $this->iterator = null;
    }

    /**
     * Returns the column names associated with the ResultSet
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return $this->column_names;
    }

    /**
     * Tell whether the CSV document record offset must be kept on output
     *
     * @return bool
     */
    public function isRecordOffsetPreserved(): bool
    {
        return $this->preserve_offset;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        return $this->iteratorToGenerator($this->iterator);
    }

    /**
     * Return the generator depending on the preserveRecordOffset setting
     *
     * @param Iterator $iterator
     *
     * @return Generator
     */
    protected function iteratorToGenerator(Iterator $iterator): Generator
    {
        if ($this->preserve_offset) {
            foreach ($iterator as $offset => $value) {
                yield $offset => $value;
            }
            return;
        }

        foreach ($iterator as $value) {
            yield $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return iterator_count($this->iterator);
    }

    /**
     * Returns a sequential array of all CSV records found
     *
     * @return array
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->iterator, $this->preserve_offset);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->fetchAll();
    }

    /**
     * Returns a the nth record from the resultset
     *
     * By default if no index is provided the first record of the resultet is returned
     *
     * @param int $nth_record the CSV record offset
     *
     * @throws OutOfRangeException if argument is lesser than 0
     *
     * @return array
     */
    public function fetchOne(int $nth_record = 0): array
    {
        if ($nth_record < 0) {
            throw new OutOfRangeException(sprintf('%s() expects the submitted offset to be a positive integer or 0, %s given', __METHOD__, $nth_record));
        }

        $iterator = new LimitIterator($this->iterator, $nth_record, 1);
        $iterator->rewind();

        return (array) $iterator->current();
    }

    /**
     * Returns the next value from a single CSV record field
     *
     * By default if no column index is provided the first column of the CSV is selected
     *
     * @param string|int $index CSV column index
     *
     * @return Generator
     */
    public function fetchColumn($index = 0): Generator
    {
        $offset = $this->getColumnIndex($index, __METHOD__.'() expects the column index to be a valid string or integer, `%s` given');
        $filter = function (array $record) use ($offset): bool {
            return isset($record[$offset]);
        };

        $select = function (array $record) use ($offset): string {
            return $record[$offset];
        };

        $iterator = new MapIterator(new CallbackFilterIterator($this->iterator, $filter), $select);

        return $this->iteratorToGenerator($iterator);
    }

    /**
     * Filter a column name against the CSV header if any
     *
     * @param string|int $field         the field name or the field index
     * @param string     $error_message the associated error message
     *
     * @return string|int
     */
    protected function getColumnIndex($field, string $error_message)
    {
        $method = 'getColumnIndexByKey';
        if (is_string($field)) {
            $method = 'getColumnIndexByValue';
        }

        return $this->$method($field, $error_message);
    }

    /**
     * Returns the selected column name
     *
     * @param string $value
     * @param string $error_message
     *
     * @throws RuntimeException if the column is not found
     *
     * @return string
     */
    protected function getColumnIndexByValue(string $value, string $error_message): string
    {
        if (false !== array_search($value, $this->column_names, true)) {
            return $value;
        }

        throw new RuntimeException(sprintf($error_message, $value));
    }

    /**
     * Returns the selected column name according to its offset
     *
     * @param int    $index
     * @param string $error_message
     *
     * @throws OutOfRangeException if the field index is invalid
     * @throws RuntimeException    if the field is invalid or not found
     *
     * @return int|string
     */
    protected function getColumnIndexByKey(int $index, string $error_message)
    {
        if ($index < 0) {
            throw new OutOfRangeException($error_message);
        }

        if (empty($this->column_names)) {
            return $index;
        }

        $value = array_search($index, array_flip($this->column_names), true);
        if (false !== $value) {
            return $value;
        }

        throw new RuntimeException(sprintf($error_message, $index));
    }

    /**
     * Fetches the next key-value pairs from a result set (first
     * column is the key, second column is the value).
     *
     * By default if no column index is provided:
     * - the first CSV column is used to provide the keys
     * - the second CSV column is used to provide the value
     *
     * @param string|int $offset_index The column index to serve as offset
     * @param string|int $value_index  The column index to serve as value
     *
     * @return Generator
     */
    public function fetchPairs($offset_index = 0, $value_index = 1): Generator
    {
        $offset = $this->getColumnIndex($offset_index, __METHOD__.'() expects the offset index value to be a valid string or integer, `%s` given');
        $value = $this->getColumnIndex($value_index, __METHOD__.'() expects the value index value to be a valid string or integer, `%s` given');

        $filter = function (array $record) use ($offset): bool {
            return isset($record[$offset]);
        };

        $select = function (array $record) use ($offset, $value): array {
            return [$record[$offset], $record[$value] ?? null];
        };

        $iterator = new MapIterator(new CallbackFilterIterator($this->iterator, $filter), $select);
        foreach ($iterator as $pair) {
            yield $pair[0] => $pair[1];
        }
    }

    /**
     * Whether we should preserve the CSV document record offset.
     *
     * If set to true CSV document record offset will be added to
     * method output where it makes sense.
     *
     * @param bool $status
     *
     * @return self
     */
    public function preserveRecordOffset(bool $status): self
    {
        $this->preserve_offset = $status;

        return $this;
    }
}
