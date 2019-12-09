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

namespace League\Csv\Plugin;

use InvalidArgumentException;

/**
 *  A class to manage column consistency on data insertion into a CSV
 *
 * @package League.csv
 * @since  7.0.0
 *
 */
class ColumnConsistencyValidator
{
    /**
     * The number of column per row
     *
     * @var int
     */
    private $columns_count = -1;

    /**
     * should the class detect the column count based the inserted row
     *
     * @var bool
     */
    private $detect_columns_count = false;

    /**
     * Set Inserted row column count
     *
     * @param int $value
     *
     * @throws InvalidArgumentException If $value is lesser than -1
     *
     */
    public function setColumnsCount(int $value)
    {
        if ($value < -1) {
            throw new InvalidArgumentException('the column count must an integer greater or equals to -1');
        }
        $this->detect_columns_count = false;
        $this->columns_count = $value;
    }

    /**
     * Column count getter
     *
     * @return int
     */
    public function getColumnsCount(): int
    {
        return $this->columns_count;
    }

    /**
     * The method will set the $columns_count property according to the next inserted row
     * and therefore will also validate the next line whatever length it has no matter
     * the current $columns_count property value.
     *
     */
    public function autodetectColumnsCount()
    {
        $this->detect_columns_count = true;
    }

    /**
     * Is the submitted row valid
     *
     * @param array $row
     *
     * @return bool
     */
    public function __invoke(array $row): bool
    {
        if ($this->detect_columns_count) {
            $this->columns_count = count($row);
            $this->detect_columns_count = false;

            return true;
        }

        if (-1 == $this->columns_count) {
            return true;
        }

        return count($row) === $this->columns_count;
    }
}
