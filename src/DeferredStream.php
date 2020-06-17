<?php

declare(strict_types=1);

namespace Bogosoft\Http;

use Psr\Http\Message\StreamInterface as IStream;

/**
 * Represents an eventual stream of data that will delay accessing data until
 * absolutely necessary.
 *
 * Calls to most methods on this class will result in a buffer being populated
 * by the copyTo method, and those methods will operate on the buffer.
 *
 * Calling the 'copyTo' method before calling any other method will simply copy
 * the contents of the current stream to the given target without utilizing the
 * buffer.
 *
 * @package Bogosoft\Http
 */
abstract class DeferredStream implements IStream, IStreamCopyable
{
    /** @var resource */
    private $buffer;

    /**
     * @inheritDoc
     */
    function __toString()
    {
        $this->rewind();

        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    function copyTo($target)
    {
        if (null === $this->buffer)
            return $this->copyToInternal($target);
        else
            return stream_copy_to_stream($this->buffer, $target);
    }

    /**
     * When overridden in a derived class, copied the content of the current
     * stream to a given resource.
     *
     * @param  resource  $target A target resource or stream to which the
     *                           contents of the current stream will be copied.
     * @return int|false         The number of bytes copied to the target
     *                           resource, or {@see false} on failure to copy.
     */
    protected abstract function copyToInternal($target);

    /**
     * @inheritDoc
     */
    function close()
    {
        if (is_resource($this->buffer))
            fclose($this->buffer);
    }

    /**
     * @inheritDoc
     */
    function detach()
    {
        try
        {
            return $this->getBuffer();
        }
        finally
        {
            $this->buffer = null;
        }
    }

    /**
     * @inheritDoc
     */
    function eof()
    {
        return feof($this->getBuffer());
    }

    /**
     * @return false|resource
     */
    private function getBuffer()
    {
        if (null === $this->buffer)
        {
            $this->buffer = fopen('php://memory', 'r+');

            $this->copyToInternal($this->buffer);

            fseek($this->buffer, 0);
        }

        return $this->buffer;
    }

    /**
     * @inheritDoc
     */
    function getContents()
    {
        return stream_get_contents($this->getBuffer());
    }

    /**
     * @inheritDoc
     */
    function getMetadata($key = null)
    {
        $data = stream_get_meta_data($this->getBuffer());

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
    function getSize()
    {
        return fstat($this->getBuffer())['size'];
    }

    /**
     * @inheritDoc
     */
    function isReadable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    function isSeekable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    function isWritable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    function read($length)
    {
        return fread($this->getBuffer(), $length);
    }

    /**
     * @inheritDoc
     */
    function rewind()
    {
        fseek($this->getBuffer(), 0);
    }

    /**
     * @inheritDoc
     */
    function seek($offset, $whence = SEEK_SET)
    {
        fseek($this->getBuffer(), $offset, $whence);
    }

    /**
     * @inheritDoc
     */
    function tell()
    {
        return ftell($this->getBuffer());
    }

    /**
     * @inheritDoc
     */
    function write($string)
    {
        return fwrite($this->getBuffer(), $string);
    }
}