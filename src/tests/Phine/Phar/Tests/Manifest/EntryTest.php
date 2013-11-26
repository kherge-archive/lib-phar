<?php

namespace Phine\Phar\Tests\Manifest;

use Phine\Path\Path;
use Phine\Phar\Manifest\Entry;
use Phine\Phar\Archive;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Performs unit tests on the `Entry` class.
 *
 * @see Entry
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class EntryTest extends TestCase
{
    /**
     * The file info instance being tested.
     *
     * @var Entry
     */
    private $file;

    /**
     * The mock archive.
     *
     * @var Archive|MockObject
     */
    private $manifest;

    /**
     * Make sure we can get the compressed file size.
     */
    public function testGetCompressedSize()
    {
        $this->assertEquals(
            5,
            $this->file->getCompressedSize(),
            'The compressed file size should be returned.'
        );
    }

    /**
     * Make sure we can get the CRC32 checksum.
     */
    public function testGetCrc32()
    {
        $this->assertEquals(
            6,
            $this->file->getCrc32(),
            'The CRC32 checksum should be returned.'
        );
    }

    /**
     * Make sure we can get the bitwise flags.
     */
    public function testGetFlags()
    {
        $this->assertEquals(
            Entry::BZ2,
            $this->file->getFlags(),
            'The bitwise flags should be returned.'
        );
    }

    /**
     * Make sure we can get the archive.
     */
    public function testGetManifest()
    {
        $this->assertSame(
            $this->manifest,
            $this->file->getArchive(),
            'The archive should be returned.'
        );
    }

    /**
     * Make sure we can get the metadata.
     */
    public function testGetMetadata()
    {
        $this->assertEquals(
            'test',
            $this->file->getMetadata(),
            'The metadata should be returned.'
        );
    }

    /**
     * Make sure we can get the size of the metadata in bytes.
     */
    public function testGetMetadataSize()
    {
        $this->assertEquals(
            0,
            $this->file->getMetadataSize(),
            'The size of the metadata should be returned.'
        );
    }

    /**
     * Make sure we can get the name of the file.
     */
    public function testGetName()
    {
        $this->assertEquals(
            'src/lib/test.php',
            $this->file->getName(),
            'The file name should be returned.'
        );
    }

    /**
     * Make sure we can get the size of the file name.
     */
    public function testGetNameSize()
    {
        $this->assertEquals(
            2,
            $this->file->getNameSize(),
            'The size of the file name should be returned.'
        );
    }

    /**
     * Make sure we can get the offset for the file data.
     */
    public function testGetOffset()
    {
        $this->assertEquals(
            1,
            $this->file->getOffset(),
            'The offset for the data should be returned.'
        );
    }

    /**
     * Make sure we can get the uncompressed files size in bytes.
     */
    public function testGetSize()
    {
        $this->assertEquals(
            3,
            $this->file->getSize(),
            'The uncompressed size of the file should be returned.'
        );
    }

    /**
     * Make sure we can get the Unix timestamp of the file.
     */
    public function testGetTimestamp()
    {
        $this->assertEquals(
            4,
            $this->file->getTimestamp(),
            'The timestamp should be returned.'
        );
    }

    /**
     * Make sure we can check if the file has metadata.
     */
    public function testHasMetadata()
    {
        $this->assertFalse(
            $this->file->hasMetadata(),
            'The file should not have metadata.'
        );

        Property::set($this->file, 'metadataSize', 1);

        $this->assertTrue(
            $this->file->hasMetadata(),
            'The file should have metadata.'
        );
    }

    /**
     * Make sure we can check the compression algorithm.
     */
    public function testIsCompressed()
    {
        $this->assertFalse(
            $this->file->isCompressed(Entry::GZ),
            'The file should not be compressed using gzip.'
        );

        $this->assertTrue(
            $this->file->isCompressed(Entry::BZ2),
            'The file should be compressed using bzip2.'
        );
    }

    /**
     * Make sure we can check if a file is actually a directory.
     */
    public function testIsDirectory()
    {
        $this->assertFalse(
            $this->file->isDirectory(),
            'The file should not be a directory.'
        );

        $file = new Entry(
            $this->manifest,
            1,
            2,
            'src/lib/test/',
            3,
            4,
            5,
            6,
            Entry::BZ2,
            0,
            'test'
        );

        $this->assertTrue(
            $file->isDirectory(),
            'This "file" should be a directory.'
        );
    }

    /**
     * Creates a new instance of `Entry` for testing.
     */
    protected function setUp()
    {
        $this->manifest = $this
            ->getMockBuilder('Phine\\Phar\\Archive')
            ->disableOriginalConstructor()
            ->getMock();

        $this->file = new Entry(
            $this->manifest,
            1,
            2,
            'src/lib/test.php',
            3,
            4,
            5,
            6,
            Entry::BZ2,
            0,
            'test'
        );
    }
}
