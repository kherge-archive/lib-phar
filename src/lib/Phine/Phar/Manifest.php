<?php

namespace Phine\Phar;

use Phine\Path\Path;
use Phine\Phar\Exception\ManifestException;
use Phine\Phar\File\Reader;
use Phine\Phar\Manifest\FileInfo;

/**
 * Reads the manifest of a PHP archive file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Manifest
{
    /**
     * The bzip2 compression flag.
     *
     * @var integer
     */
    const BZ2 = 0x2000;

    /**
     * The gzip compression flag.
     *
     * @var integer
     */
    const GZ = 0x1000;

    /**
     * The flag mask.
     *
     * @var integer
     */
    const MASK = 0x3000;

    /**
     * The starting offset of the manifest.
     *
     * @var integer
     */
    private $offset;

    /**
     * The archive file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The sequence of characters that precedes the manifest.
     *
     * @var array
     */
    private static $sequence = array(
        '_', '_', 'h', 'a', 'l', 't',
        '_', 'c', 'o', 'm', 'p', 'i', 'l', 'e', 'r',
        '(', ')', ';', ' ', '?', '>'
    );

    /**
     * Sets the PHP archive file reader and manifest offset.
     *
     * If an offset is not provided `findOffset()` is called to discover the
     * offset.
     *
     * @param Reader  $reader The archive file reader.
     * @param integer $offset The manifest offset.
     *
     * @throws ManifestException If the offset is not set and no offset could
     *                           be found using the given archive reader.
     */
    public function __construct(Reader $reader, $offset = null)
    {
        if (null === $offset) {
            if (null === ($offset = self::findOffset($reader))) {
                throw ManifestException::offsetNotFound($reader->getFile());
            }
        }

        $this->offset = $offset;
        $this->reader = $reader;
    }

    /**
     * Finds the manifest offset of an archive.
     *
     * @param Reader $reader The archive file reader.
     *
     * @return integer If the manifest offset is found, it is returned. If a
     *                 manifest offset is not found, `null` is returned.
     */
    public static function findOffset(Reader $reader)
    {
        $reader->seek(0);

        $offset = 0;

        while (!$reader->isEndOfFile()) {
            if (strtolower($reader->read(1)) === self::$sequence[$offset]) {
                $offset++;
            } else {
                $offset = 0;
            }

            if (!isset(self::$sequence[$offset + 1])) {
                break;
            }
        }

        $reader->seek(1, SEEK_CUR);

        if ($offset === (count(self::$sequence) - 1)) {
            $position = $reader->getPosition();

            if ("\r\n" === $reader->read(2)) {
                return $position + 2;
            }

            return $position;
        }

        return null;
    }

    /**
     * Returns the stream alias.
     *
     * @return string The alias in the manifest.
     */
    public function getAlias()
    {
        if (0 < ($size = $this->getAliasSize())) {
            return $this->reader->read($size);
        }

        return null;
    }

    /**
     * Returns the size of the stream alias in bytes.
     *
     * @return integer The size of the alias in bytes.
     */
    public function getAliasSize()
    {
        $this->reader->seek($this->offset + 14);

        return $this->readLong();
    }

    /**
     * Returns the API version of the manifest.
     *
     * @return string The API version of the manifest.
     */
    public function getApiVersion()
    {
        $this->reader->seek($this->offset + 8);

        $version = unpack('H*', $this->reader->read(2));
        $version = hexdec($version[1]);

        // @codeCoverageIgnoreStart
        $version = sprintf(
            '%u.%u.%u',
            $version >> 12,
            ($version >> 8) & 0xF,
            ($version >> 4) & 0xF
        );
        // @codeCoverageIgnoreEnd

        return $version;
    }

    /**
     * Returns the number of files listed in the manifest.
     *
     * @return integer The number of files in the manifest.
     */
    public function getFileCount()
    {
        $this->reader->seek($this->offset + 4);

        return $this->readLong();
    }

    /**
     * Returns the list of files in the manifest.
     *
     * @return FileInfo[] The list of files in the manifest.
     */
    public function getFileList()
    {

        $count = $this->getFileCount();
        $size = $this->getSize() + 4;

        $this->reader->seek(
            $this->offset
            + 18
            + $this->getAliasSize()
            + 4
            + $this->getMetadataSize()
        );

        return $this->readFileList($count, $size);
    }

    /**
     * Returns the global bitmapped flags in the manifest.
     *
     * @return integer The global bitmapped flags.
     */
    public function getGlobalFlags()
    {
        $this->reader->seek($this->offset + 10);

        return $this->readLong() & self::MASK;
    }

    /**
     * Returns the byte offset of the manifest.
     *
     * @return integer The byte offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Return the unserialized metadata of the manifest.
     *
     * @return mixed The unserialized metadata.
     *
     * @throws ManifestException If the metadata is corrupt or invalid.
     */
    public function getMetadata()
    {
        if (0 === ($size = $this->getMetadataSize())) {
            return null;
        }

        return $this->readData($size);
    }

    /**
     * Returns the size of the metadata in bytes.
     *
     * @return integer The size of the metadata in bytes.
     */
    public function getMetadataSize()
    {
        $this->reader->seek($this->offset + 18 + $this->getAliasSize());

        return $this->readLong();
    }

    /**
     * Returns the archive file reader.
     *
     * @return Reader The archive file reader.
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Returns the size of the manifest in bytes.
     *
     * @return integer The size of the manifest in bytes.
     */
    public function getSize()
    {
        $this->reader->seek($this->offset);

        return $this->readLong();
    }

    /**
     * Reads and unserializes data.
     *
     * @param integer $size The size of the metadata.
     *
     * @return mixed The unserialized data.
     */
    private function readData($size)
    {
        return unserialize($this->reader->read($size));
    }

    /**
     * Reads the expected number of files from the manifest.
     *
     * @param integer $expected The expected number of files.
     * @param integer $size     The size of the manifest.
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
            $file['name']['data'] = $this->reader->read($file['name']['size']);
            $file['size']['uncompressed'] = $this->readLong();
            $file['time'] = $this->readLong();
            $file['size']['compressed'] = $this->readLong();
            $file['crc32'] = $this->readLong();
            $file['flags'] = $this->readLong() & self::MASK;
            $file['metadata']['size'] = $this->readLong();

            if ($file['metadata']['size']) {
                $file['metadata']['data'] = $this->readData(
                    $file['metadata']['size']
                );
            }

            $file['name']['data'] = Path::split($file['name']['data']);
            $file['name']['data'] = join('/', $file['name']['data']);

            $offset += $file['size']['compressed'];
            $files[] = new FileInfo(
                $file['offset'],
                $file['name']['size'],
                $file['name']['data'],
                $file['size']['uncompressed'],
                $file['time'],
                $file['size']['compressed'],
                $file['crc32'],
                $file['flags'],
                $file['metadata']['size'],
                $file['metadata']['data']
            );
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
        $long = unpack('V', $this->reader->read(4));
        $long = (int) $long[1];

        return $long;
    }
}
