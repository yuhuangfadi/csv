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

use League\Csv\Exception\RuntimeException;
use Traversable;

/**
 * A class to convert CSV records into a DOMDOcument object
 *
 * @package League.csv
 * @since   9.0.0
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 */
class JsonConverter implements Converter
{
    use ValidatorTrait;

    /**
     * json_encode options
     *
     * @var int
     */
    protected $options = 0;

    /**
     * json_encode $options flag setter
     *
     * @param int $options
     *
     * @return self
     */
    public function options(int $options): self
    {
        $clone = clone $this;
        $clone->options = $this->filterMinRange($options, 0, __METHOD__.': the options must be a positive integer or 0');

        return $clone;
    }

    /**
     * Convert an Record collection into a Json string
     *
     * @param array|Traversable $records the CSV records collection
     *
     * @return string
     */
    public function convert($records)
    {
        $records = $this->filterIterable($records, __METHOD__);
        if (!is_array($records)) {
            $records = iterator_to_array($records);
        }

        $json = @json_encode($records, $this->options, 2);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        }

        throw new RuntimeException(json_last_error_msg());
    }
}
