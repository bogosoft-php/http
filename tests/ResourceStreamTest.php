<?php

declare(strict_types=1);

namespace Tests;

use Bogosoft\Http\Stream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResourceStreamTest extends TestCase
{
    function testAttemptingToReadNonReadableStreamThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = Stream::open('php://stdout', 'w');

        $this->assertFalse($stream->isReadable());

        $stream->read(8);
    }

    function testAttemptingToSeekOnNonSeekableStreamThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = Stream::open('php://stdout', 'wb');

        $this->assertFalse($stream->isSeekable());

        $stream->seek(16);
    }

    function testAttemptingToWriteToNonWritableStreamThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = Stream::open('php://stdin', 'r');

        $this->assertFalse($stream->isWritable());

        $stream->write('Hello, World!');
    }

    /**
     * @depends testCanRewind
     * @depends testCanWrite
     */
    function testCanCopyContents(): void
    {
        $expected = 'Hello, World!';

        $source = Stream::openMemory();

        $source->write($expected);

        $source->rewind();

        /** @var resource $target */
        $target = null;

        try
        {
            $target = fopen('php://memory', 'r+');

            $source->copyTo($target);

            fseek($target, 0);

            $actual = stream_get_contents($target);

            $this->assertEquals($expected, $actual);
        }
        finally
        {
            if (is_resource($target))
                fclose($target);
        }
    }

    /**
     * @depends testCanRewind
     * @depends testCanSeek
     * @depends testCanWrite
     */
    function testCanGetContents(): void
    {
        $stream = Stream::openMemory();

        $expected = 'Hello, World!';

        $stream->write($expected);

        $this->assertEquals('', $stream->getContents());

        $position = 5;

        $stream->seek($position);

        $this->assertEquals(substr($expected, $position), $stream->getContents());

        $stream->rewind();

        $this->assertEquals($expected, $stream->getContents());
    }

    /**
     * @depends testCanRewind
     * @depends testCanWrite
     */
    function testCanRead(): void
    {
        $stream = Stream::openMemory();

        $this->assertTrue($stream->isReadable());

        $data = 'Hello, World!';

        $stream->write($data);

        $stream->rewind();

        $actual = $stream->read(5);

        $this->assertEquals(substr($data, 0, 5), $actual);
    }

    /**
     * @depends testCanWrite
     */
    function testCanRewind(): void
    {
        $stream = Stream::openMemory('w');

        $data = 'Hello, World!';

        $stream->write($data);

        $this->assertGreaterThan(0, $stream->tell());

        $stream->rewind();

        $this->assertEquals(0, $stream->tell());
    }

    /**
     * @depends testCanWrite
     */
    function testCanSeek(): void
    {
        $stream = Stream::openMemory('w');

        $data = 'Hello, World!';

        $stream->write($data);

        $this->assertGreaterThan(0, $stream->tell());

        $stream->seek(0);

        $this->assertEquals(0, $stream->tell());
    }

    function testCanWrite(): void
    {
        $stream = Stream::openMemory('w');

        $this->assertTrue($stream->isWritable());

        $this->assertEquals(0, $stream->tell());

        $length = $stream->write('Hello, World!');

        $this->assertGreaterThan(0, $length);
        $this->assertGreaterThan(0, $stream->tell());
    }

    function testInvalidFilenameThrowsRuntimeExceptionOnOpen(): void
    {
        $this->expectException(RuntimeException::class);

        Stream::open('not-a-file.txt', 'r');
    }

    function testInvalidModeThrowsRuntimeExceptionOnOpen(): void
    {
        $this->expectException(RuntimeException::class);

        $path = __FILE__;

        $this->assertTrue(is_file($path));

        Stream::open($path, 'z');
    }

    function testInvalidSchemeThrowsRuntimeExceptionOnOpen(): void
    {
        $this->expectException(RuntimeException::class);

        Stream::open('not-a-scheme://' . __FILE__, 'r');
    }

    function testMagicToStringMethodReturnsAllContentsRegardlessOfStreamPosition(): void
    {
        $stream = Stream::open('php://memory', 'r+');

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());

        $expected = 'Hello, World!';

        $stream->write($expected);

        $this->assertGreaterThan(0, $stream->tell());

        $this->assertEquals($expected, $stream->__toString());

        $stream->rewind();

        $this->assertEquals(0, $stream->tell());

        $this->assertEquals($expected, $stream->__toString());
    }

    function testMagicToStringMethodReturnsEmptyStringForNonReadableStream(): void
    {
        $stream = Stream::open('php://memory', 'w');

        $this->assertFalse($stream->isReadable());
        $this->assertTrue($stream->isWritable());

        $stream->write('Hello, World!');

        $stream->rewind();

        $this->assertEquals('', $stream->__toString());
    }
}
