<?php

namespace Phine\Phar\File;

use Phine\Exception\Exception;
use Phine\Phar\Exception\FileException;

/**
 * Manages strict file writing operations.
 *
 * @author Kevin Herrera <kevin@herrera.io>
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
     * @param string $file The path of the file.
     *
     * @throws Exception
     * @throws FileException If the file path is not valid.
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
            if (false === ($this->handle = @fopen($this->file, 'wb'))) {
                throw FileException::createUsingLastError();
            }
        }

        return $this->handle;
    }

    /**
     * Writes a specific number of bytes to the file.
     *
     * @param string  $string The string to write to the file.
     * @param integer $length The number of bytes of the string to write.
     *
     * @throws Exception
     * @throws FileException If the file could not be written.
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
