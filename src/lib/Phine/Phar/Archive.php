<?php

namespace Phine\Phar;

use Phine\Path\Path;
use Phine\Phar\Exception\ArchiveException;
use Phine\Phar\File\Reader;
use Phine\Phar\Manifest\Entry;

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
 * To start, you will need to create a new instance of `Archive`.
 *
 *     use Phine\Phar\File\Reader;
 *     use Phine\Phar\Archive;
 *
 *     $reader = new Reader('example.phar');
 *     $archive = new Archive($reader, 1234);
 *
 * You may also just want to use the `create()` method.
 *
 *     use Phine\Phar\Archive;
 *
 *     $archive = Archive::create('example.phar', 1234);
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
     * Creates a new instance of this class.
     *
     * This method will create a new file reader and use it to create a new
     * instance of this class.
     *
     *     use Phine\Phar\Archive;
     *
     *     $archive = Archive::create('example.phar', 1234);
     *
     * @param string  $file              The archive file path.
     * @param integer $offset (optional) The data offset.
     *
     * @return Archive The new instance.
     */
    public static function create($file, $offset = null)
    {
        return new self(new Reader($file), $offset);
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
     * Returns the list of entries in manifest for this archive.
     *
     * This method will return the entries listed in the archive's manifest as
     * instances of `Entry`.
     *
     *     $files = $archive->getEntries();
     *
     * @return Entry[] The list of files in this archive.
     *
     * @api
     */
    public function getEntries()
    {

        $count = $this->getEntryCount();
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
     * Returns the number of files listed in the archive for this archive.
     *
     *     $fileCount = $archive->getEntryCount();
     *
     * @return integer The number of files in this archive.
     *
     * @api
     */
    public function getEntryCount()
    {
        $this->reader->seek($this->offset + 4);

        return $this->readLong();
    }

    /**
     * Returns the global bitmapped flags for this archive.
     *
     * This method will return a bitmapped value for the global flags set for
     * the archive. This value is not the same as the ones used on a per file
     * basis.
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
     * This method will return the size of the manifest file in the archive
     * as bytes.
     *
     *     $size = $archive->getManifestSize();
     *
     * > It may be important to know what the manifest contains. It is simply
     * > a list of files and empty directories in the archive file, along with
     * > other information specific to each list entry. It does not contain
     * > data such as the global bitmapped flags or the archive stream alias.
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
     * This method will return the unserialized global metadata for the archive.
     *
     *     $metadata = $archive->getMetadata();
     *
     * If no metadata has been set, `null` is returned. Note, however, that
     * this is may not be true since it could have been a serialized `null`
     * value. To confirm that no data was provided, you will want to use the
     * `hasMetadata()` method.
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
     * This method will return the size of the global metadata for the archive.
     *
     *     $size = $archive->getMetadataSize();
     *
     * If the size is `0` (zero), no metadata was set.
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
     * This method will return the file reader used for reading the archive.
     *
     *     $reader = $archive->getReader();
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
     * Checks if the archive has global metadata.
     *
     * This method will check to see if the archive actually has metadata.
     *
     *     if ($archive->hasMetadata()) {
     *         // has metadata
     *     }
     *
     * @return boolean If the archive has metadata, `true` is returned. If the
     *                 archive does not have metadata, `false` is returned.
     *
     * @api
     */
    public function hasMetadata()
    {
        return (0 < $this->getMetadataSize());
    }

    /**
     * Reads and unserializes data.
     *
     * This method will read that has been serialized and stored in the
     * archive file. The read data will then be returned unserialized.
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
     * This method will parse the given number of files from the archive's
     * manifest. Each entry in the manifest will be added to an array as an
     * instance of `Entry`. The `$expected` and `$size` parameters are not
     * precise, garbage data may be read which would result in the reader
     * throwing an exception.
     *
     * @param integer $expected The expected number of files.
     * @param integer $size     The size of the archive.
     *
     * @return Entry[] The list of files.
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
            $files[] = new Entry(
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
     * This method will read an unsigned long (little-endian byte order) from
     * the file and return the unpacked value.
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
