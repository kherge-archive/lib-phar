<?php

namespace Phine\Phar;

use Phine\Path\Path;
use Phine\Phar\Exception\ArchiveException;
use Phine\Phar\File\Reader;
use Phine\Phar\Manifest\FileInfo;

/**
 * Parses archives built using the phar archive format.
 *
 * Summary
 * -------
 *
 * The `Archive` class parses parts of an archive file that uses the [phar
 * file format](http://us3.php.net/manual/en/phar.fileformat.phar.php). It
 * extracts information such as the archive alias, metadata, and
 * [archive](http://us3.php.net/manual/en/phar.fileformat.manifestfile.php)
 * without using the `phar` extension.
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Archive`:
 *
 *     use Phine\Phar\File\Reader;
 *     use Phine\Phar\Archive;
 *
 *     $reader = new Reader('example.phar');
 *     $archive = new Archive($reader, 1234);
 *
 * The number `1234` is actually the offset of where the archive data will
 * actually begin. While this number is optional, I recommend that it be given
 * if it is known. If the offset is not provided, the class will scan the whole
 * archive to find it. This process can be expensive, depending on the size of
 * archive file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Archive
{
    /**
     * The bzip2 compression flag.
     *
     * @api
     */
    const BZ2 = 0x2000;

    /**
     * The gzip compression flag.
     *
     * @api
     */
    const GZ = 0x1000;

    /**
     * The flag mask.
     */
    const MASK = 0x3000;

    /**
     * The data offset for this archive.
     *
     * @var integer
     */
    private $offset;

    /**
     * The file reader for this archive.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The sequence of characters that precedes the data.
     *
     * @var array
     */
    private static $sequence = array(
        '_', '_', 'h', 'a', 'l', 't',
        '_', 'c', 'o', 'm', 'p', 'i', 'l', 'e', 'r',
        '(', ')', ';', ' ', '?', '>'
    );

    /**
     * Sets the PHP archive file reader and data offset.
     *
     * If an offset is not provided `findOffset()` is called to discover the
     * offset. Since `findOffset()` can be expensive to call when using large
     * archive files, it is recommended that the offset be provided if it is
     * known.
     *
     *     use Phine\Phar\Archive;
     *     use Phine\Phar\File\Reader;
     *
     *     $reader = new Reader('example.phar');
     *     $archive = new Archive($reader, 1234);
     *
     * @param Reader  $reader            The archive file reader.
     * @param integer $offset (optional) The data offset.
     *
     * @throws ArchiveException If the offset is not set and no offset could
     *                          be found using the given archive reader.
     *
     * @api
     */
    public function __construct(Reader $reader, $offset = null)
    {
        if (null === $offset) {
            if (null === ($offset = self::findOffset($reader))) {
                throw ArchiveException::offsetNotFound($reader->getFile());
            }
        }

        $this->offset = $offset;
        $this->reader = $reader;
    }

    /**
     * Finds the data offset of an archive.
     *
     * This method will scan the entire archive for a specific sequence of
     * characters that denotes the end of the archive's stub. In an archive
     * file using the phar file format, the data follows the end of the stub.
     * If the sequence is located, the offset for the data will be returned.
     * If the sequence could not be found, nothing (`null`) is returned.
     *
     *     use Phine\Phar\File\Reader;
     *     use Phine\Phar\Archive;
     *
     *     $reader = new Reader('example.phar');
     *     $offset = Archive::findOffset($reader);
     *
     * @link http://us1.php.net/manual/en/phar.fileformat.phar.php File Format
     *
     * @param Reader $reader The archive file reader.
     *
     * @return integer If the archive offset is found, it is returned. If a
     *                 archive offset is not found, `null` is returned.
     *
     * @api
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
     * Returns the stream alias for this archive.
     *
     * This method will return the stream alias that was set for the archive
     * when it was created. This alias can be used to stream files from the
     * archive using functions such as `file_get_contents()`.
     *
     *     $alias = $archive->getAlias();
     *
     * @see Phar::__construct()
     * @see Phar::setAlias()
     *
     * @return string The stream alias for the archive.
     *
     * @api
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
     * This method will return the size of the archive's stream alias.
     *
     *     $aliasSize = $archive->getAliasSize();
     *
     * > It may be interesting to note that the size returned and the actual
     * > data for the stream alias are two separate bits of data. It may be
     * > possible for an archive to be corrupted, reporting the wrong size
     * > for the alias.
     *
     * @return integer The size of the alias in bytes.
     *
     * @api
     */
    public function getAliasSize()
    {
        $this->reader->seek($this->offset + 14);

        return $this->readLong();
    }

    /**
     * Returns the version of the archive file format.
     *
     * This method will return the version of the file format used for this
     * archive file.
     *
     *     $version = $archive->getApiVersion();
     *
     * > Note that this version number is not to be confused with the version
     * > number returned by `Phar::apiVersion()`, as that version number is
     * > used for new archives.
     *
     * @see Phar::getVersion
     *
     * @return string The version of the archive file formats.
     *
     * @api
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
     * Returns the number of files listed in the archive for this archive.
     *
     *     $fileCount = $archive->getFileCount();
     *
     * @return integer The number of files in this archive.
     *
     * @api
     */
    public function getFileCount()
    {
        $this->reader->seek($this->offset + 4);

        return $this->readLong();
    }

    /**
     * Returns the list of files in manifest for this archive.
     *
     * This method will return the files listed in the archive's manifest as
     * instances of `FileInfo`.
     *
     *     $files = $archive->getFileList();
     *
     * @return FileInfo[] The list of files in this archive.
     *
     * @api
     */
    public function getFileList()
    {

        $count = $this->getFileCount();
        $size = $this->getManifestSize() + 4;

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
     * Returns the global bitmapped flags for this archive.
     *
     *     $flags = $archive->getFlags();
     *
     * @return integer The global bitmapped flags.
     *
     * @api
     */
    public function getGlobalFlags()
    {
        $this->reader->seek($this->offset + 10);

        return $this->readLong() & self::MASK;
    }

    /**
     * Returns the byte offset for the data of this archive.
     *
     * This method will return the offset in the archive file of where the
     * raw data begins.
     *
     *     $offset = $archive->getOffset();
     *
     * @return integer The byte offset.
     *
     * @api
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns the size of the manifest for this archive in bytes.
     *
     * @return integer The size of the manifest in bytes.
     *
     * @api
     */
    public function getManifestSize()
    {
        $this->reader->seek($this->offset);

        return $this->readLong();
    }

    /**
     * Return the unserialized global metadata for this archive.
     *
     * @return mixed The unserialized global metadata.
     *
     * @throws ArchiveException If the global metadata is corrupt or invalid.
     *
     * @api
     */
    public function getMetadata()
    {
        if (0 === ($size = $this->getMetadataSize())) {
            return null;
        }

        return $this->readData($size);
    }

    /**
     * Returns the size of metadata in this archive in bytes.
     *
     * @return integer The size of the metadata in bytes.
     *
     * @api
     */
    public function getMetadataSize()
    {
        $this->reader->seek($this->offset + 18 + $this->getAliasSize());

        return $this->readLong();
    }

    /**
     * Returns the file reader for this archive.
     *
     * @return Reader The file reader for this archive.
     *
     * @api
     */
    public function getReader()
    {
        return $this->reader;
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
     * Reads the expected number of files from the archive.
     *
     * @param integer $expected The expected number of files.
     * @param integer $size     The size of the archive.
     *
     * @return FileInfo[] The list of files.
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
                $this,
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
