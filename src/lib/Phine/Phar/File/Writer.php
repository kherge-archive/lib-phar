<?php

namespace Phine\Phar\File;

use Phine\Exception\Exception;
use Phine\Phar\Exception\FileException;

/**
 * Manages strict file writing operations.
 *
 * Summary
 * -------
 *
 * The `Writer` class provides a way to write files with heavy error checking.
 * Instead of subtly triggering warnings, exceptions are thrown if something
 * could not be done (e.g. fopen(), fwrite() a specific number of bytes, etc).
 *
 * Starting
 * --------
 *
 * To start, you need to create a new instance of `Writer`.
 *
 *     use Phine\Phar\File\Writer;
 *
 *     $writer = new Writer('/path/to/file');
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Writer
{
    /**
     * The path to the file.
     *
     * @var string
     */
    private $file;

    /**
     * The file handle.
     *
     * @var resource
     */
    private $handle;

    /**
     * Sets the path of the file for writing.
     *
     * This method will set the path of the file to be written.
     *
     *     use Phine\Phar\File\Writer;
     *
     *     $writer = new Writer('/path/to/file');
     *
     * > Note that the file will not be opened until the first write operation
     * > is attempted. An exception will still be thrown, however, if the
     * > directory for the file does not exist, or if the file exists but is
     * > not writable.
     *
     * @param string $file The path of the file.
     *
     * @throws Exception
     * @throws FileException If the file path is not valid.
     *
     * @api
     */
    public function __construct($file)
    {
        if (!is_dir($dir = dirname($file))) {
            throw FileException::createUsingFormat(
                'The directory "%s" does not exist.',
                $dir
            );
        }

        if (is_file($file) && !is_writable($file)) {
            throw FileException::createUsingFormat(
                'The file "%s" exists but is not writable.',
                $file
            );
        }

        $this->file = $file;
    }

    /**
     * Closes the file if it's open.
     *
     * This method will close the file handle if it has been opened.
     *
     *     use Phine\Phar\File\Writer;
     *
     *     $writer = new Writer('/path/to/file');
     *
     *     $reader->write($data);
     *
     *     unset($reader);
     *
     * @api
     */
    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }

    /**
     * Returns the path of the file.
     *
     * This method will return the path to the file being written.
     *
     *     $file = $writer->getFile();
     *
     * @return string The path of the file.
     *
     * @api
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns the writing file handle.
     *
     * This method will return the writing file handle for the file.
     *
     *     $handle = $writer->getHandle();
     *
     * > Note that the file will be opened for writing binary data.
     *
     * @return resource The file handle.
     *
     * @throws Exception
     * @throws FileException If the file could not be opened for reading.
     *
     * @api
     */
    public function getHandle()
    {
        if (null === $this->handle) {
            if (false === ($this->handle = @fopen($this->file, 'wb'))) {
                throw FileException::createUsingLastError();
            }
        }

        return $this->handle;
    }

    /**
     * Writes a specific number of bytes to the file.
     *
     * This method will attempt to write the exact string, or the length
     * specified. If the whole string, or the specified length, could not
     * be written, an exception will be thrown.
     *
     *     $writer->write($data);
     *
     * @param string  $string The string to write to the file.
     * @param integer $length The number of bytes of the string to write.
     *
     * @throws Exception
     * @throws FileException If the file could not be written.
     *
     * @api
     */
    public function write($string, $length = null)
    {
        if (null === $length) {
            $length = strlen($string);
        }

        if (false === ($actual = @fwrite($this->getHandle(), $string, $length))) {
            throw FileException::createUsingLastError();
        }

        if ($length !== $actual) {
            throw FileException::createUsingFormat(
                'Only %d of %d bytes were written to "%s".',
                $actual,
                $length,
                $this->file
            );
        }
    }
}
