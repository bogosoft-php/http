<?php

declare(strict_types=1);

namespace Bogosoft\Http;

use Psr\Http\Message\StreamInterface as IStream;
use RuntimeException;

/**
 * A general-purpose implementation of the {@see IStream} and
 * {@see IStreamCopyable} contracts. Streams created from this class wrap
 * PHP resources.
 *
 * @package Bogosoft\Http
 */
class Stream implements IStream, IStreamCopyable
{
    private const FLAG_READABLE = 0x01;
    private const FLAG_SEEKABLE = 0x02;
    private const FLAG_WRITABLE = 0x04;

    /**
     * Create a new stream from a resource handle.
     *
     * @param  resource $handle A resource handle.
     * @return Stream           A new stream.
     */
    static function from($handle): Stream
    {
        $mode = stream_get_meta_data($handle)['mode'];

        return self::fromInternal($handle, $mode);
    }

    private static function fromInternal($handle, string $mode): Stream
    {
        $flags = 0;

        $mode = str_replace('b', '', $mode);
        $mode = str_replace('e', '', $mode);
        $mode = str_replace('t', '', $mode);

        if (in_array($mode, ['a+', 'c+', 'r', 'r+', 'w+', 'x+']))
            $flags |= self::FLAG_READABLE;

        if (in_array($mode, ['a', 'a+', 'c', 'c+', 'r+', 'w', 'w+', 'x', 'x+']))
            $flags |= self::FLAG_WRITABLE;

        $data = stream_get_meta_data($handle);

        if (true === $data['seekable'] ?? false)
            $flags |= self::FLAG_SEEKABLE;

        return new Stream($handle, $flags);
    }

    /**
     * Open a new stream.
     *
     * @param  string  $uri     The URI of a resource to wrap in a stream.
     * @param  string  $mode    The mode of the new stream.
     * @param  null    $context A context object to use during for stream
     *                          operations.
     * @return  Stream          A new stream.
     */
    static function open(string $uri, string $mode, $context = null): Stream
    {
        @$handle = null === $context
            ? fopen($uri, $mode)
            : fopen($uri, $mode, $context);

        if (false === $handle)
        {
            $error = error_get_last();

            throw new RuntimeException($error['message'], $error['type']);
        }

        return self::fromInternal($handle, $mode);
    }

    /**
     * Open a stream to a memory handle.
     *
     * @param  string $mode The mode in which to open a memory handle.
     * @return Stream       A new stream.
     */
    static function openMemory(string $mode = 'r+'): Stream
    {
        return self::open('php://memory', $mode);
    }

    /**
     * Open a handle to a memory stream populated with a given string.
     *
     * The resulting memory stream will be rewound after the given data has
     * been written to it.
     *
     * @param  string $data Data to be populated once the memory stream has
     *                      been opened.
     * @param  string $mode The mode in which the new stream will operate.
     * @return Stream       A new stream.
     *
     * @throws RuntimeException if the given mode makes the new memory stream
     *                          non-writable.
     */
    static function using(string $data, string $mode = 'r+'): Stream
    {
        $stream = self::openMemory($mode);

        $stream->write($data);

        $stream->rewind();

        return $stream;
    }

    private int $flags;

    /** @var resource */
    private $handle;

    private function __construct($handle, int $flags)
    {
        $this->flags  = $flags;
        $this->handle = $handle;
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream is either non-seekable
     *                          or non-readable.
     */
    public function __toString()
    {
        if (0 === ($this->flags & self::FLAG_READABLE))
            return '';

        $this->seek(0);

        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if (is_resource($this->handle))
            fclose($this->handle);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the copy operation failed.
     */
    function copyTo($target)
    {
        try
        {
            return stream_copy_to_stream($this->handle, $target) ?: 0;
        }
        finally
        {
            if (null !== $error = error_get_last())
                throw new RuntimeException($error['message'], $error['type']);
        }
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        try
        {
            return $this->handle;
        }
        finally
        {
            $this->handle = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        return feof($this->handle);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream is not readable.
     */
    public function getContents()
    {
        if (0 === ($this->flags & self::FLAG_READABLE))
            throw new RuntimeException('Stream is not readable.');

        return stream_get_contents($this->handle);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        $data = stream_get_meta_data($this->handle);

        if (null === $key)
            return $data;
        elseif (array_key_exists($key, $data))
            return $data[$key];
        else
            return null;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return fstat($this->handle)['size'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        return 0 !== ($this->flags & self::FLAG_READABLE);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return 0 !== ($this->flags & self::FLAG_SEEKABLE);
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        return 0 !== ($this->flags & self::FLAG_WRITABLE);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream is not readable.
     */
    public function read($length)
    {
        if (0 === ($this->flags & self::FLAG_READABLE))
            throw new RuntimeException('Stream is not readable.');

        return fread($this->handle, $length);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream does not support seeks.
     */
    public function rewind()
    {
        if (0 === ($this->flags & self::FLAG_SEEKABLE))
            throw new RuntimeException('Stream is not seekable.');

        fseek($this->handle, 0);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream does not support seeks.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (0 === ($this->flags & self::FLAG_SEEKABLE))
            throw new RuntimeException('Stream is not seekable.');

        fseek($this->handle, $offset, $whence);
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        return ftell($this->handle);
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException if the current stream is non-writable.
     */
    public function write($string)
    {
        if (0 === ($this->flags & self::FLAG_WRITABLE))
            throw new RuntimeException('Stream is not writable.');

        return fwrite($this->handle, $string);
    }
}
