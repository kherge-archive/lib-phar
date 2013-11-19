<?php

namespace Phine\Phar\File;

use Phine\Exception\Exception;
use Phine\Phar\Exception\FileException;

/**
 * Manages strict file reading operations.
 *
 * @author Kevin Herrera <kevin@herrera.io>
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
     * @param string $file The path of the file.
     *
     * @throws Exception
     * @throws FileException If the file path is not valid.
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
     * @return string The path of the file.
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns the reading file handle.
     *
     * @return resource The file handle.
     *
     * @throws Exception
     * @throws FileException If the file could not be opened for reading.
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
     * @return integer The current position.
     *
     * @throws Exception
     * @throws FileException If the size could not be read.
     */
    public function getPosition()
    {
        if (false === ($position = @ftell($this->getHandle()))) {
            throw FileException::createUsingLastError();
        }

        return $position;
    }

    /**
     * Returns the size of the file.
     *
     * @return integer The size of the file.
     *
     * @throws Exception
     * @throws FileException If the size could not be read.
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
     * @return boolean If the end of the file has been reached, `false` is
     *                 returned. If the end of the file has not been reached,
     *                 `true` is returned.
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
     * @param integer $bytes The number of bytes.
     *
     * @return string The bytes read.
     *
     * @throws Exception
     * @throws FileException If not all the bytes were read.
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
     * @param integer $offset The offset.
     * @param integer $whence The direction.
     *
     * @throws Exception
     * @throws FileException If seeking failed.
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
