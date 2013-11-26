<?php

namespace Phine\Phar\File;

use Phine\Exception\Exception;
use Phine\Phar\Exception\FileException;

/**
 * Manages strict file reading operations.
 *
 * Summary
 * -------
 *
 * The `Reader` class provides a way to read files with heavy error checking.
 * Instead of subtly triggering warnings, exceptions are thrown if something
 * could not be done (e.g. fseek(), fread() a specific number of bytes, etc).
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Reader`.
 *
 *     use Phine\Phar\File\Reader;
 *
 *     $reader = new Reader('/path/to/file');
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Reader
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
     * Sets the path of the file for reading.
     *
     * This method will set the path of the file to be read.
     *
     *     use Phine\Phar\File\Reader;
     *
     *     $reader = new Reader('/path/to/file');
     *
     * > Note that the file will not be opened until the first read operation
     * > is attempted. An exception will still be thrown, however, if the file
     * > does not exist.
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
        if (!is_file($file)) {
            throw FileException::createUsingFormat(
                'The path "%s" is not a file or does not exist.',
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
     *     use Phine\Phar\File\Reader;
     *
     *     $reader = new Reader('/path/to/file');
     *
     *     $data = $reader->read(10);
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
     * This method will return the path to the file being read.
     *
     *     $file = $reader->getFile();
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
     * Returns the reading file handle.
     *
     * This method will return the reading file handle for the file.
     *
     *     $handle = $reader->getHandle();
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
            if (false === ($this->handle = @fopen($this->file, 'rb'))) {
                throw FileException::createUsingLastError();
            }
        }

        return $this->handle;
    }

    /**
     * Returns the current position of the file pointer.
     *
     * This method will return the result of `fseek()` on the file handle.
     *
     *     $position = $reader->getPosition();
     *
     * @return integer The current position.
     *
     * @throws Exception
     * @throws FileException If the size could not be read.
     *
     * @api
     */
    public function getPosition()
    {
        if (false === ($position = @ftell($this->getHandle()))) {
            throw FileException::createUsingLastError();
        }

        return $position;
    }

    /**
     * Returns the byte size of the file.
     *
     * This method will return the size of the file on disk as bytes.
     *
     *     $size = $reader->getSize();
     *
     * @return integer The size of the file in bytes.
     *
     * @throws Exception
     * @throws FileException If the size could not be read.
     *
     * @api
     */
    public function getSize()
    {
        if (false === ($size = @filesize($this->file))) {
            throw FileException::createUsingLastError();
        }

        return $size;
    }

    /**
     * Checks if the end of the file has been reached.
     *
     * Before `feof()` is called, an attempt is made to reach a single byte
     * from the file. If the read fails, then the `feof()` is made to actually
     * determine the if the end of the file has been reached. If the EOF is not
     * reached, the file handle will be seeked back the one byte that was read.
     *
     *     while (!$reader->isEndOfFile()) {
     *         // not at the end yet
     *     }
     *
     * @return boolean If the end of the file has been reached, `true` is
     *                 returned. If the end of the file has not been reached,
     *                 `false` is returned.
     *
     * @api
     */
    public function isEndOfFile()
    {
        $handle = $this->getHandle();

        if (false === @fgetc($handle)) {

            return feof($handle);
        } else {
            fseek($handle, -1, SEEK_CUR);
        }

        return false;
    }

    /**
     * Reads a specific number of bytes from the file.
     *
     * This method will attempt to read the exact number of bytes specified.
     * If the exact number of bytes could not be read, an exception will be
     * thrown.
     *
     *     $data = $reader->read(10);
     *
     * @param integer $bytes The number of bytes.
     *
     * @return string The bytes read.
     *
     * @throws Exception
     * @throws FileException If not all the bytes were read.
     *
     * @api
     */
    public function read($bytes)
    {
        $read = '';
        $total = $bytes;

        while (!$this->isEndOfFile() && $bytes) {
            if (false === ($chunk = @fread($this->getHandle(), $bytes))) {
                throw FileException::createUsingLastError();
            }

            $read .= $chunk;
            $bytes -= strlen($chunk);
        }

        if ($total !== ($actual = strlen($read))) {
            throw FileException::createUsingFormat(
                'Only read %d bytes of %d from "%s".',
                $actual,
                $total,
                $this->file
            );
        }

        return $read;
    }

    /**
     * Seeks to a specific point in the file.
     *
     * This method will seek to the specified offset in the file.
     *
     *     $reader->seek(10, SEEK_CUR);
     *
     * @param integer $offset The offset.
     * @param integer $whence The direction.
     *
     * @throws Exception
     * @throws FileException If seeking failed.
     *
     * @api
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (-1 === @fseek($this->getHandle(), $offset, $whence)) {
            throw FileException::createUsingFormat(
                'Could not seek to %d in "%s".',
                $offset,
                $this->file
            );
        }
    }
}
