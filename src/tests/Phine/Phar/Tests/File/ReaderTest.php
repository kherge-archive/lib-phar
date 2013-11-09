<?php

namespace Phine\Phar\Tests\File;

use Phine\Phar\Exception\FileException;
use Phine\Phar\File\Reader;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Reader} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ReaderTest extends TestCase
{
    /**
     * The reader instance being tested.
     *
     * @var Reader
     */
    private $reader;

    /**
     * Make sure that we can specify the file for reading.
     */
    public function testConstruct()
    {
        $this->assertEquals(
            __FILE__,
            Property::get($this->reader, 'file'),
            'Make sure we can set the file to read.'
        );
    }

    /**
     * Make sure that we can only specify files that exist.
     */
    public function testConstructNotExist()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'The path "/does/not/exist" is not a file or does not exist.'
        );

        new Reader('/does/not/exist');
    }

    /**
     * Make sure an open file handle is closed.
     */
    public function testDestruct()
    {
        $handle = $this->reader->getHandle();

        $this->reader = null;

        $this->setExpectedException(
            'PHPUnit_Framework_Error_Warning',
            'is not a valid stream'
        );

        fwrite($handle, 'test');
    }

    /**
     * Make sure that we can get the path of the file being read.
     */
    public function testGetFile()
    {
        $this->assertEquals(
            __FILE__,
            $this->reader->getFile(),
            'Make sure we can get the path.'
        );
    }

    /**
     * Make sure we can get an open file handle.
     */
    public function testGetHandle()
    {
        $this->assertEquals(
            'stream',
            get_resource_type($this->reader->getHandle()),
            'Make sure we get a file handle.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't open the file.
     */
    public function testGetHandleError()
    {
        Property::set($this->reader, 'file', '/does/not/exist');

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'failed to open stream'
        );

        $this->reader->getHandle();
    }

    /**
     * Make sure we can get the position of the file handle.
     */
    public function testGetPosition()
    {
        $this->assertEquals(
            0,
            $this->reader->getPosition(),
            'Make sure we get the current position.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't get the position.
     */
    public function testGetPositionError()
    {
        Property::set($this->reader, 'handle', 'test');

        try {
            $this->reader->getPosition();
        } catch (FileException $exception) {
        }

        Property::set($this->reader, 'handle', null);

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'expects parameter 1 to be resource, string given'
        );

        /** @noinspection PhpUndefinedVariableInspection */
        throw $exception;
    }

    /**
     * Make sure that we can get the size of a file.
     */
    public function testGetSize()
    {
        $this->assertEquals(
            filesize(__FILE__),
            $this->reader->getSize(),
            'Make sure we can get the file size.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't open the file.
     */
    public function testGetSizeError()
    {
        Property::set($this->reader, 'file', '/does/not/exist');

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'stat failed'
        );

        $this->reader->getSize();
    }

    /**
     * Make sure we can check if we reached the end of the file.
     */
    public function testIsEndOfFile()
    {
        $this->assertFalse(
            $this->reader->isEndOfFile(),
            'Make sure we are not at the end of the file.'
        );

        fseek(Property::get($this->reader, 'handle'), 0, SEEK_END);

        $this->assertTrue(
            $this->reader->isEndOfFile(),
            'Make sure we are at the end of the file.'
        );
    }

    /**
     * Make sure we can read all of the specified bytes.
     */
    public function testRead()
    {
        $this->assertEquals(
            file_get_contents(__FILE__),
            $this->reader->read(filesize(__FILE__)),
            'Make sure we can read the file.'
        );
    }

    /**
     * Make sure an exception is thrown if reading failed.
     */
    public function testReadError()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'Length parameter must be greater than 0'
        );

        $this->reader->read(-4);
    }

    /**
     * Make sure an exception is thrown if not all the bytes were read.
     */
    public function testReadNotEnough()
    {
        $size = filesize(__FILE__);

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            sprintf(
                'Only read %d bytes of %d from "%s".',
                $size,
                $size + 1,
                __FILE__
            )
        );

        $this->reader->read($size + 1);
    }

    /**
     * Make sure we can seek to a point in a file.
     */
    public function testSeek()
    {
        $size = filesize(__FILE__);
        $offset = round($size / 2);

        $this->reader->seek($offset);

        $this->assertEquals(
            $offset,
            ftell(Property::get($this->reader, 'handle')),
            'Make sure we seek to the point specified.'
        );
    }

    /**
     * Make sure an exception is thrown if the seek failed.
     */
    public function testSeekError()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'Could not seek to 1 in "' . __FILE__ . '".'
        );

        $this->reader->seek(1, 123);
    }

    /**
     * Creates a new instance of {@link Reader} for testing.
     */
    protected function setUp()
    {
        $this->reader = new Reader(__FILE__);
    }
}
