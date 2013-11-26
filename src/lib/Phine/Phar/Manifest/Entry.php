<?php

namespace Phine\Phar\Manifest;

use Phine\Phar\Archive;

/**
 * Manages the information of an individual manifest entry.
 *
 * Summary
 * -------
 *
 * The `Entry` class manages an individual entry in the manifest of an archive
 * file. For file entries, the class does not directly manages the contents of
 * a file. Instead, the class is used by the `Extract` class to know where a
 * file begins and how much should be read from the archive.
 *
 * Using
 * -----
 *
 * Instances of this class are returned by the `Archive::getEntries()` method.
 * Instantiation of the class itself is intended to be limited to the library
 * itself, and is therefore not part of the public API. The remaining methods
 * (i.e. no the `__construct()` method) are part of the public API.
 *
 *     $entries = $archive->getEntries();
 *
 * @link http://us1.php.net/manual/en/phar.fileformat.manifestfile.php Manifest File Entry Definition
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Entry
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
     * The archive this entry was read from.
     *
     * @var Archive
     */
    private $archive;

    /**
     * The compressed byte size of this entry.
     *
     * @var integer
     */
    private $compressedSize;

    /**
     * The CRC32 checksum for this entry.
     *
     * @var integer
     */
    private $crc32;

    /**
     * The bitwise flags for this entry.
     *
     * @var integer
     */
    private $flags;

    /**
     * The metadata for this entry.
     *
     * @var mixed
     */
    private $metadata;

    /**
     * The byte size of the metadata for this entry.
     *
     * @var integer
     */
    private $metadataSize;

    /**
     * The name of this entry.
     *
     * @var string
     */
    private $name;

    /**
     * The byte size of the name for this entry.
     *
     * @var integer
     */
    private $nameSize;

    /**
     * The offset for this entry's file data.
     *
     * @var integer
     */
    private $offset;

    /**
     * The uncompressed byte size of this entry's file data.
     *
     * @var integer
     */
    private $size;

    /**
     * The Unix timestamp of this entry.
     *
     * @var integer
     */
    private $timestamp;

    /**
     * Sets the information for this entry.
     *
     * @param Archive  $archive        The archive the entry was read from.
     * @param integer  $offset         The offset for the file data.
     * @param integer  $nameSize       The byte size of the name.
     * @param string   $name           The name.
     * @param integer  $size           The uncompressed byte size.
     * @param integer  $timestamp      The Unix timestamp.
     * @param integer  $compressedSize The compressed byte size.
     * @param integer  $crc32          The CRC32 checksum.
     * @param integer  $flags          The bitwise flags.
     * @param integer  $metadataSize   The byte size of the metadata.
     * @param mixed    $metadata       The metadata.
     *
     * @internal
     */
    public function __construct(
        Archive $archive,
        $offset,
        $nameSize,
        $name,
        $size,
        $timestamp,
        $compressedSize,
        $crc32,
        $flags,
        $metadataSize,
        $metadata
    ) {
        $this->compressedSize = $compressedSize;
        $this->crc32 = $crc32;
        $this->flags = $flags;
        $this->archive = $archive;
        $this->metadata = $metadata;
        $this->metadataSize = $metadataSize;
        $this->name = $name;
        $this->nameSize = $nameSize;
        $this->offset = $offset;
        $this->size = $size;
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the archive this entry was read from.
     *
     * This method will return the archive file reader that was used to
     * retrieve this manifest entry.
     *
     *     $archive = $entry->getArchive();
     *
     * @return Archive The archive.
     *
     * @api
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Returns the compressed byte size of the file data for this entry.
     *
     * This method will return the size of the file data for this entry in
     * bytes. The size returned may not be the actual size of the file data
     * before it was been compressed (e.g. bzip2, gzip).
     *
     *     $size = $entry->getCompressedSize();
     *
     * @return integer The compressed size.
     *
     * @api
     */
    public function getCompressedSize()
    {
        return $this->compressedSize;
    }

    /**
     * Returns the CRC32 checksum for this entry.
     *
     * This method will return the CRC32 checksum of the file data of this
     * entry. If the file has been compressed, the checksum only applies to
     * the uncompressed file data for this entry.
     *
     *     $crc32 = $entry->getCrc32();
     *
     * @return integer The checksum.
     *
     * @api
     */
    public function getCrc32()
    {
        return $this->crc32;
    }

    /**
     * Returns the bitmapped flags for this entry.
     *
     * This method will return the bitmapped flags for the entry. The flags
     * could be used to identify compression methods used on the file data
     * stored in the archive.
     *
     *     $flags = $entry->getFlags();
     *
     * @return integer The bitwise flags.
     *
     * @api
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Returns the unserialized metadata for this entry.
     *
     * This method will return the unserialized metadata for the entry.
     *
     *     $metadata = $entry->getMetadata();
     *
     * If no metadata has been set, `null` is returned. Note, however, that
     * this is may not be true since it could have been a serialized `null`
     * value. To confirm that no data was provided, you will want to use the
     * `hasMetadata()` method.
     *
     * @return mixed The metadata.
     *
     * @api
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Returns the byte size of the metadata for this entry.
     *
     * This method will return the size of the metadata for the entry.
     *
     *     $size = $entry->getMetadataSize();
     *
     * If the size is `0` (zero), no metadata was set.
     *
     * @return integer The byte size of the metadata.
     *
     * @api
     */
    public function getMetadataSize()
    {
        return $this->metadataSize;
    }

    /**
     * Returns the name of this entry.
     *
     * This method will return the name of the entry as used inside the archive.
     *
     *     $name = $entry->getName();
     *
     * If this entry is for a directory, the name will have a trailing slash.
     *
     * @return string The name of the file.
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the byte size of the name for this entry.
     *
     * This method will return the size of the name for this entry in bytes.
     *
     *     $size = $entry->getNameSize();
     *
     * @return integer The byte size of the name for this entry.
     *
     * @api
     */
    public function getNameSize()
    {
        return $this->nameSize;
    }

    /**
     * Returns the offset for the file data of this entry.
     *
     * This method will return the offset in the archive file of where the
     * contents of this file data begins for this entry. It is primarily used
     * by the `Extract` class to know where to begin extracting an individual
     * file.
     *
     *     $offset = $entry->getOffset();
     *
     * @return integer The offset for the data.
     *
     * @api
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns the uncompressed byte size of the file data for this entry.
     *
     * This method will return the byte size of the uncompressed file data for
     * this entry. Note that this may not necessarily be the same of the data
     * as stored in the archive file (on disk). For that, you will need to use
     * the `Entry::getCompressedSize()` method.
     *
     *     $size = $entry->getSize();
     *
     * @return integer The uncompressed size.
     *
     * @api
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Returns the Unix timestamp of the entry.
     *
     * This method will return the Unix timestamp of when the entry was added
     * to the manifest.
     *
     *     $timestamp = $entry->getTimestamp();
     *
     * @return integer The Unix timestamp.
     *
     * @api
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Checks if the entry has metadata.
     *
     * This method will check to see if the entry actually has metadata.
     *
     *     if ($entry->hasMetadata()) {
     *         // has metadata
     *     }
     *
     * @return boolean If the file has metadata, `true` is returned. If the
     *                 file does not have metadata, `false` is returned.
     *
     * @api
     */
    public function hasMetadata()
    {
        return (0 < $this->metadataSize);
    }

    /**
     * Checks if the file data is compressed using the specified algorithm.
     *
     * This method will check to see if the file data for this entry has been
     * compressed using a specific compression algorithm.
     *
     *     use Phine\Phar\Manifest\Entry;
     *
     *     if ($entry->isCompressed(Entry::BZ2)) {
     *         // compressed using bzip2
     *     } elseif ($entry->isCompressed(Entry::GZ)) {
     *         // compressed using gzip
     *     } else {
     *         // not compressed
     *     }
     *
     * @param integer $algorithm The algorithm to check.
     *
     * @return boolean If the file is compressed, `true` is returned. If the
     *                 file is not compressed using the specified algorithm,
     *                 `false` is returned.
     *
     * @api
     */
    public function isCompressed($algorithm)
    {
        return (0 < ($this->flags & $algorithm));
    }

    /**
     * Checks if this entry is a directory.
     *
     * This method will check if this manifest entry is for an empty directory.
     *
     *     if ($entry->isDirectory()) {
     *         // is directory
     *     }
     *
     * @return boolean If this instance is for a directory inside the archive,
     *                 `true` is returned. If this instance is for a file,
     *                 `false` is returned.
     */
    public function isDirectory()
    {
        return ('/' === substr($this->name, -1, 1));
    }
}
