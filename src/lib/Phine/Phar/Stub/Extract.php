<?php

namespace Phine\Phar\Stub;

use InvalidArgumentException;
use RuntimeException;

/**
 * Embeddable, self-contained archive extractor.
 *
 * The purpose of this extraction class is for embedding it into the stub.
 * This will allow archives to extract themselves for execution when the
 * extension is not installed. Unlike the extraction class provided by the
 * default stub, error checking is performed to provide better diagnostic
 * information if an archive is corrupt.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
final class Extract
{
    /**
     * The bzip2 compression flag.
     *
     * @internal
     */
    const BZ2 = 0x2000;

    /**
     * The gzip compression flag.
     *
     * @internal
     */
    const GZ = 0x1000;

    /**
     * The flag mask.
     *
     * @internal
     */
    const MASK = 0x3000;

    /**
     * The list of support compression algorithms.
     *
     * @var array
     */
    private $compression = array();

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
     * The starting offset of the archive.
     *
     * @var integer
     */
    private $offset;

    /**
     * The sequence of characters that precedes the archive.
     *
     * @var array
     */
    private static $sequence = array(
        '_', '_', 'h', 'a', 'l', 't',
        '_', 'c', 'o', 'm', 'p', 'i', 'l', 'e', 'r',
        '(', ')', ';', ' ', '?', '>'
    );

    /**
     * Sets the path of the file for reading.
     *
     * @param string $file The path of the file.
     *
     * @throws InvalidArgumentException If the file path is not valid.
     *
     * @internal
     */
    public function __construct($file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(
                "The path \"$file\" is not a file or does not exist."
            );
        }

        $this->compression['bzip2'] = function_exists('bzdecompress');
        $this->compression['gzip'] = function_exists('gzinflate');
        $this->file = $file;
    }

    /**
     * Closes the file if it's open.
     *
     * @internal
     */
    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }

    /**
     * Creates a new instance using the given archive file path.
     *
     * @param string $file The archive file path.
     *
     * @return Extract The new instance.
     *
     * @internal
     */
    public static function from($file)
    {
        return new self($file);
    }

    /**
     * Returns the embeddable source for this class.
     *
     * This method will return the compacted source code of this class so
     * that it can be embedded as part of the stub. Note that this is the
     * only method in the class that is part of the public API.
     *
     *     use Phine\Phar\Builder;
     *     use Phine\Phar\Stub;
     *     use Phine\Phar\Stub\Extract;
     *
     *     Builder::create('example.phar')->setStub(
     *         Stub::create()
     *             ->addSource(Extract::getSource())
     *             ->getStub()
     *     );
     *
     * @return string The source code.
     *
     * @throws RuntimeException If the file could not be read.
     *
     * @api
     */
    public static function getSource()
    {
        if (false === ($code = @file(__FILE__))) {
            $error = error_get_last();

            throw new RuntimeException($error['message']);
        }

        $code = array_slice($code, 6);

        foreach ($code as $i => $line) {
            if ('' === trim($line)) {
                unset($code[$i]);
                continue;
            }

            if (preg_match('{^\s*(/\*+|\*+)}', $line)) {
                unset($code[$i]);
            }
        }

        return join('', $code);
    }

    /**
     * Extracts one or more files to an output directory.
     *
     * @param string $dir The output directory path.
     *
     * @return string The output directory path.
     *
     * @throws RuntimeException If a file could not be extracted.
     *
     * @internal
     */
    public function to($dir = null)
    {
        if (null === $dir) {
            $dir = sprintf(
                '%s%spharextract%s%s',
                sys_get_temp_dir(),
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                basename($this->file, '.phar')
            );
        }

        $check = $dir . DIRECTORY_SEPARATOR . md5_file($this->file);

        if (file_exists($check)) {
            return $dir;
        }

        if (null === $this->offset) {
            $this->offset = $this->findOffset();
        }

        foreach ($this->getFileList() as $file) {
            $path = $dir . '/' . $file['name']['data'];
            $base = dirname($path);

            if (!is_dir($base)) {
                if (!@mkdir($base, 0755, true)) {
                    $error = error_get_last();

                    throw new RuntimeException($error['message']);
                }
            }

            if (!@file_put_contents($path, $this->extractFile($file))) {
                $error = error_get_last();

                throw new RuntimeException($error['message']);
            }
        }

        if (!@touch($check)) {
            $error = error_get_last();

            throw new RuntimeException($error['message']);
        }

        return $dir;
    }

    /**
     * Returns the contents of a file from the archive.
     *
     * @param array $file The file information from the archive.
     *
     * @return string The decompressed file contents.
     *
     * @throws RuntimeException If the file could not be decompressed.
     */
    private function extractFile(array $file)
    {
        $this->seek($file['offset']);

        $contents = $this->read($file['size']['compressed']);

        if ($file['flags'] & self::BZ2) {
            if (!$this->compression['bzip2']) {
                throw new RuntimeException(
                    "The \"bz2\" extension is required to decompress \"{$file['name']['data']}\"."
                );
            }

            $contents = bzdecompress($contents);
        } elseif ($file['flags'] & self::GZ) {
            if (!$this->compression['gzip']) {
                throw new RuntimeException(
                    "The \"zlib\" extension is required to decompress \"{$file['name']['data']}\"."
                );
            }

            $contents = gzinflate($contents);
        }

        return $contents;
    }

    /**
     * Finds the archive offset of an archive.
     *
     * @return integer If the archive offset is found, it is returned. If a
     *                 archive offset is not found, `null` is returned.
     */
    private function findOffset()
    {
        $this->seek(0);

        $offset = 0;

        while (!$this->isEndOfFile()) {
            if (strtolower($this->read(1)) === self::$sequence[$offset]) {
                $offset++;
            } else {
                $offset = 0;
            }

            if (!isset(self::$sequence[$offset + 1])) {
                break;
            }
        }

        $this->seek(1, SEEK_CUR);

        if ($offset === (count(self::$sequence) - 1)) {
            $position = $this->getPosition();

            if ("\r\n" === $this->read(2)) {
                return $position + 2;
            }

            return $position;
        }

        return null;
    }

    /**
     * Returns the size of the stream alias in bytes.
     *
     * @return integer The size of the alias in bytes.
     */
    private function getAliasSize()
    {
        $this->seek($this->offset + 14);

        return $this->readLong();
    }

    /**
     * Returns the number of files listed in the archive.
     *
     * @return integer The number of files in the archive.
     */
    private function getFileCount()
    {
        $this->seek($this->offset + 4);

        return $this->readLong();
    }

    /**
     * Returns the list of files in the archive.
     *
     * @return array The list of files in the archive.
     */
    private function getFileList()
    {

        $count = $this->getFileCount();
        $size = $this->getSize() + 4;

        $this->seek(
            $this->offset
            + 18
            + $this->getAliasSize()
            + 4
            + $this->getMetadataSize()
        );

        return $this->readFileList($count, $size);
    }

    /**
     * Returns the reading file handle.
     *
     * @return resource The file handle.
     *
     * @throws RuntimeException If the file could not be opened for reading.
     */
    private function getHandle()
    {
        if (null === $this->handle) {
            if (false === ($this->handle = @fopen($this->file, 'rb'))) {
                $error = error_get_last();

                throw new RuntimeException($error['message']);
            }
        }

        return $this->handle;
    }

    /**
     * Returns the size of the metadata in bytes.
     *
     * @return integer The size of the metadata in bytes.
     */
    private function getMetadataSize()
    {
        $this->seek($this->offset + 18 + $this->getAliasSize());

        return $this->readLong();
    }

    /**
     * Returns the current position of the file pointer.
     *
     * @return integer The current position.
     *
     * @throws RuntimeException If the size could not be read.
     */
    private function getPosition()
    {
        if (false === ($position = @ftell($this->getHandle()))) {
            $error = error_get_last();

            throw new RuntimeException($error['message']);
        }

        return $position;
    }

    /**
     * Returns the size of the archive in bytes.
     *
     * @return integer The size of the archive in bytes.
     */
    private function getSize()
    {
        $this->seek($this->offset);

        return $this->readLong();
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
    private function isEndOfFile()
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
     * @throws RuntimeException If not all the bytes were read.
     */
    private function read($bytes)
    {
        $read = '';
        $total = $bytes;

        while (!$this->isEndOfFile() && $bytes) {
            if (false === ($chunk = @fread($this->getHandle(), $bytes))) {
                $error = error_get_last();

                throw new RuntimeException($error['message']);
            }

            $read .= $chunk;
            $bytes -= strlen($chunk);
        }

        if ($total !== ($actual = strlen($read))) {
            throw new RuntimeException(
                "Only read $actual bytes of $total from \"{$this->file}\"."
            );
        }

        return $read;
    }

    /**
     * Reads the expected number of files from the archive.
     *
     * @param integer $expected The expected number of files.
     * @param integer $size     The size of the archive.
     *
     * @return array The list of files.
     */
    private function readFileList($expected, $size)
    {
        $files = array();
        $offset = $this->offset + $size;

        for ($i = 0; $i < $expected; $i++) {
            $file = array(
                'crc32'=> null,
                'flags' => null,
                'metadata' => array(
                    'data' => null,
                    'size' => null,
                ),
                'name' => array(
                    'data' => null,
                    'size' => null,
                ),
                'offset' => $offset,
                'size' => array(
                    'compressed' => null,
                    'uncompressed' => null,
                ),
                'time' => null,
            );

            $file['name']['size'] = $this->readLong();
            $file['name']['data'] = $this->read($file['name']['size']);
            $file['size']['uncompressed'] = $this->readLong();
            $file['time'] = $this->readLong();
            $file['size']['compressed'] = $this->readLong();
            $file['crc32'] = $this->readLong();
            $file['flags'] = $this->readLong() & self::MASK;
            $file['metadata']['size'] = $this->readLong();

            $offset += $file['size']['compressed'];
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Reads and unpacks an unsigned long.
     *
     * @return integer The unsigned long.
     */
    private function readLong()
    {
        $long = unpack('V', $this->read(4));
        $long = (int) $long[1];

        return $long;
    }

    /**
     * Seeks to a specific point in the file.
     *
     * @param integer $offset The offset.
     * @param integer $whence The direction.
     *
     * @throws RuntimeException If seeking failed.
     */
    private function seek($offset, $whence = SEEK_SET)
    {
        if (-1 === @fseek($this->getHandle(), $offset, $whence)) {
            throw new RuntimeException(
                "Could not seek to $offset in \"{$this->file}\"."
            );
        }
    }
}
