<?php

/**
* This file is part of the bakame.psr7-csv-factory library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/bakame-php/psr7-csv-factory
* @version 1.0.0
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace LeagueTest\Csv;

final class StreamWrapper
{
    const PROTOCOL = 'leaguetest';

    public $context;

    private $stream;

    public static function register()
    {
        if (!in_array(self::PROTOCOL, stream_get_wrappers())) {
            stream_wrapper_register(self::PROTOCOL, __CLASS__);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $options = stream_context_get_options($this->context);
        if (!isset($options[self::PROTOCOL]['stream'])) {
            return false;
        }

        $this->stream = $options[self::PROTOCOL]['stream'];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_read($count)
    {
        return fread($this->stream, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_write($data)
    {
        return fwrite($this->stream, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_tell()
    {
        return ftell($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_eof()
    {
        return feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_seek($offset, $whence)
    {
        fseek($this->stream, $whence);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_stat()
    {
        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 33206,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }
}
