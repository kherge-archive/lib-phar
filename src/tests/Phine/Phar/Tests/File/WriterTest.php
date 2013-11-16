<?php

namespace Phine\Phar\Tests\File;

use Phine\Phar\Exception\FileException;
use Phine\Phar\File\Writer;
use Phine\Test\Property;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Writer} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class WriterTest extends TestCase
{
    /**
     * The file being written.
     *
     * @var string
     */
    private $file;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * The writer instance being tested.
     *
     * @var Writer
     */
    private $writer;

    /**
     * Make sure that we can specify the file for writing.
     */
    public function testConstruct()
    {
        $this->assertEquals(
            $this->file,
            Property::get($this->writer, 'file'),
            'Make sure the file is what we set it to be.'
        );
    }

    /**
     * Make sure an exception is thrown if the parent directory does not exist.
     */
    public function testConstructDirNotExist()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'The directory "/does/not" does not exist.'
        );

        new Writer('/does/not/exist');
    }

    /**
     * Make sure an open file handle is closed.
     */
    public function testDestruct()
    {
        $handle = $this->writer->getHandle();

        $this->writer = null;

        $this->setExpectedException(
            'PHPUnit_Framework_Error_Warning',
            'is not a valid stream'
        );

        fwrite($handle, 'test');
    }

    /**
     * Make sure that we can get the path of the file being written.
     */
    public function testGetFile()
    {
        $this->assertEquals(
            $this->file,
            $this->writer->getFile(),
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
            get_resource_type($this->writer->getHandle()),
            'Make sure we get a file handle.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't open the file.
     */
    public function testGetHandleError()
    {
        Property::set($this->writer, 'file', '/does/not/exist');

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'failed to open stream'
        );

        $this->writer->getHandle();
    }

    /**
     * Make sure we can write to the file.
     */
    public function testWrite()
    {
        $this->writer->write('test');

        $this->writer = null;

        $this->assertEquals(
            'test',
            file_get_contents($this->file),
            'Make sure the file contents are written.'
        );
    }

    /**
     * Make sure an exception is thrown if we could not write to the file.
     */
    public function testWriteError()
    {
        fclose($this->writer->getHandle());

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'is not a valid stream resource'
        );

        try {
            $this->writer->write('test');
        } catch (FileException $exception) {
            Property::set($this->writer, 'handle', null);

            throw $exception;
        }
    }

    /**
     * Make sure an exception is thrown if not all of the bytes were written.
     */
    public function testWriteBytesShort()
    {
        Property::set($this->writer, 'handle', opendir(__DIR__));

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'Only 0 of 4 bytes were written to "' . $this->file . '".'
        );

        try {
            $this->writer->write('test');
        } catch (FileException $exception) {
            closedir($this->writer->getHandle());

            Property::set($this->writer, 'handle', null);


            throw $exception;
        }
    }

    /**
     * Creates a new instance of {@link Writer} for testing.
     */
    protected function setUp()
    {
        $this->temp = new Temp();
        $this->file = $this->temp->createFile();

        $this->writer = new Writer($this->file);
    }

    /**
     * Cleans up the temporary paths.
     */
    protected function tearDown()
    {
        $this->temp->purgePaths();
    }
}
