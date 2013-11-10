<?php

namespace Phine\Phar\Manifest;

/**
 * Manages the information of an individual manifest file entry.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileInfo
{
    /**
     * The compressed byte size of the file.
     *
     * @var integer
     */
    private $compressedSize;

    /**
     * The CRC32 checksum for the file.
     *
     * @var integer
     */
    private $crc32;

    /**
     * The bitwise flags for the file.
     *
     * @var integer
     */
    private $flags;

    /**
     * The metadata for the file.
     *
     * @var mixed
     */
    private $metadata;

    /**
     * The byte size of the metadata for the file.
     *
     * @var integer
     */
    private $metadataSize;

    /**
     * The name of the file.
     *
     * @var string
     */
    private $name;

    /**
     * The byte size of the file name.
     *
     * @var integer
     */
    private $nameSize;

    /**
     * The offset for the file data.
     *
     * @var integer
     */
    private $offset;

    /**
     * The uncompressed byte size of the file.
     *
     * @var integer
     */
    private $size;

    /**
     * The Unix timestamp of the file.
     *
     * @var integer
     */
    private $timestamp;

    /**
     * Sets the information for the file.
     *
     * @param integer $offset         The offset for the file data.
     * @param integer $nameSize       The byte size of the name.
     * @param string  $name           The name.
     * @param integer $size           The uncompressed byte size.
     * @param integer $timestamp      The Unix timestamp.
     * @param integer $compressedSize The compressed byte size.
     * @param integer $crc32          The CRC32 checksum.
     * @param integer $flags          The bitwise flags.
     * @param integer $metadataSize   The byte size of the metadata.
     * @param mixed   $metadata       The metadata.
     */
    public function __construct(
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
        $this->metadata = $metadata;
        $this->metadataSize = $metadataSize;
        $this->name = $name;
        $this->nameSize = $nameSize;
        $this->offset = $offset;
        $this->size = $size;
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the compressed byte size of the file.
     *
     * @return integer The compressed size.
     */
    public function getCompressedSize()
    {
        return $this->compressedSize;
    }

    /**
     * Returns the CRC32 checksum for the file.
     *
     * @return integer The checksum.
     */
    public function getCrc32()
    {
        return $this->crc32;
    }

    /**
     * Returns the bitwise flags for the file.
     *
     * @return integer The bitwise flags.
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Returns the metadata for the file.
     *
     * @return mixed The metadata.
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Returns the byte size of the metadata for the file.
     *
     * @return integer The byte size of the metadata.
     */
    public function getMetadataSize()
    {
        return $this->metadataSize;
    }

    /**
     * Returns the name of the file.
     *
     * @return string The name of the file.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the byte size of the file name.
     *
     * @return integer The byte size of the file name.
     */
    public function getNameSize()
    {
        return $this->nameSize;
    }

    /**
     * Returns the offset for the file data.
     *
     * @return integer The offset for the data.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns the uncompressed byte size of the file.
     *
     * @return integer The uncompressed size.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Returns the Unix timestamp of the file.
     *
     * @return integer The Unix timestamp.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Checks if the file has metadata.
     *
     * @return boolean If the file has metadata, `true` is returned. If the
     *                 file does not have metadata, `false` is returned.
     */
    public function hasMetadata()
    {
        return (0 < $this->metadataSize);
    }

    /**
     * Checks if the file is compressed using the specified algorithm.
     *
     * @param integer $algorithm The algorithm to check.
     *
     * @return boolean If the file is compressed, `true` is returned. If the
     *                 file is not compressed using the specified algorithm,
     *                 `false` is returned.
     */
    public function isCompressed($algorithm)
    {
        return (0 < ($this->flags & $algorithm));
    }
}
