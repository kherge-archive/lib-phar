<?php

namespace Phine\Phar\Tests;

use Phine\Phar\Manifest;
use Phine\Phar\File\Reader;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Manifest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ManifestTest extends TestCase
{
    /**
     * The archive file being used for testing.
     *
     * @var string
     */
    private $file;

    /**
     * The manifest instance being tested.
     *
     * @var Manifest
     */
    private $manifest;

    /**
     * The archive file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * Make sure that the offset is found and the reader is set.
     *
     * If the offset of the manifest is not provided, make sure that it is
     * located in valid archives. If the manifest offset could not be found,
     * an exception should be thrown. If the offset is provided, make sure
     * that it is set along with the archive reader.
     */
    public function testConstruct()
    {
        $this->assertSame(
            $this->reader,
            Property::get($this->manifest, 'reader'),
            'The file reader should be set.'
        );

        $this->assertEquals(
            94,
            Property::get($this->manifest, 'offset'),
            'The manifest offset should be 94.'
        );

        $manifest = new Manifest($this->reader, 123);

        $this->assertEquals(
            123,
            Property::get($manifest, 'offset'),
            'The manifest offset should be 123.'
        );

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\ManifestException',
            sprintf(
                'The manifest offset could not be found in the PHP archive file "%s".',
                __FILE__
            )
        );

        new Manifest(new Reader(__FILE__));
    }

    /**
     * Make sure that we can retrieve the offset of a manifest, if possible.
     */
    public function testFindOffset()
    {
        // using a custom stub
        $this->assertEquals(
            94,
            Manifest::findOffset(new Reader($this->file)),
            'The offset returned should be 94.'
        );

        $this->assertNull(
            Manifest::findOffset(new Reader(__FILE__)),
            'No offset should be found in non-archive files.'
        );

        // using the default stub
        $this->assertEquals(
            6683,
            Manifest::findOffset(new Reader(dirname($this->file) . '/default.phar')),
            'The offset returned should be 6683.'
        );
    }

    /**
     * Make sure we can read the alias of an archive.
     */
    public function testGetAlias()
    {
        $this->assertEquals(
            'test.phar',
            $this->manifest->getAlias(),
            'The alias "test.phar" should be returned.'
        );

        $manifest = new Manifest(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertNull(
            $manifest->getAlias(),
            'There should be no alias returned.'
        );
    }

    /**
     * Make sure we can get the size of the alias.
     */
    public function testGetAliasSize()
    {
        $this->assertEquals(
            9,
            $this->manifest->getAliasSize(),
            'The size of the alias should be 9.'
        );

        $manifest = new Manifest(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertEquals(
            0,
            $manifest->getAliasSize(),
            'The size of the alias should be 0.'
        );
    }

    /**
     * Make sure we can read the API version of an archive.
     */
    public function testGetApiVersion()
    {
        $this->assertEquals(
            '1.1.0',
            $this->manifest->getApiVersion(),
            'The version "1.1.0" should be returned.'
        );
    }

    /**
     * Make sure we can retrieve the file count for an archive.
     */
    public function testGetFileCount()
    {
        $this->assertEquals(
            2,
            $this->manifest->getFileCount(),
            'There should be 2 files in example.phar.'
        );

        $manifest = new Manifest(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertEquals(
            0,
            $manifest->getFileCount(),
            'There should be no files in no-alias.phar.'
        );
    }

    /**
     * Make sure we can retrieve the list of files.
     *
     * The list returned is an array of arrays. Each value of the list contains
     * detailed information about the file in the archive. This information can
     * be used to extract files.
     */
    public function testGetFileList()
    {
        $files = $this->manifest->getFileList();

        $this->assertEquals(4091535927, $files[0]->getCrc32());
        $this->assertEquals(0, $files[0]->getFlags());
        $this->assertEquals(0, $files[0]->getMetadataSize());
        $this->assertNull($files[0]->getMetadata());
        $this->assertEquals('bin/main', $files[0]->getName());
        $this->assertEquals(8, $files[0]->getNameSize());
        $this->assertEquals(264, $files[0]->getOffset());
        $this->assertEquals(77, $files[0]->getCompressedSize());
        $this->assertEquals(77, $files[0]->getSize());
        $this->assertEquals(1383980172, $files[0]->getTimestamp());
        $this->assertFalse($files[0]->hasMetadata());

        $this->assertEquals(2063568359, $files[1]->getCrc32());
        $this->assertEquals(0, $files[1]->getFlags());
        $this->assertEquals(30, $files[1]->getMetadataSize());
        $this->assertEquals(array('rand' => 1317613458), $files[1]->getMetadata());
        $this->assertEquals('src/Put.php', $files[1]->getName());
        $this->assertEquals(11, $files[1]->getNameSize());
        $this->assertEquals(341, $files[1]->getOffset());
        $this->assertEquals(104, $files[1]->getCompressedSize());
        $this->assertEquals(104, $files[1]->getSize());
        $this->assertEquals(1383980172, $files[1]->getTimestamp());
    }

    /**
     * Make sure we can get the archive's global flags.
     */
    public function testGetGlobalFlags()
    {
        $this->assertEquals(
            0,
            $this->manifest->getGlobalFlags(),
            'The global flags should be returned.'
        );
    }

    /**
     * Make sure we can get the offset for the manifest.
     */
    public function testGetOffset()
    {
        $this->assertEquals(
            94,
            $this->manifest->getOffset(),
            'The offset for the manifest should be 94.'
        );
    }

    /**
     * Make sure we can retrieve the metadata.
     */
    public function testGetMetadata()
    {
        $this->assertEquals(
            array(
                'who' => 'It was me!'
            ),
            $this->manifest->getMetadata(),
            'The metadata should be returned.'
        );

        $manifest = new Manifest(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertNull(
            $manifest->getMetadata(),
            'There should be no metadata in no-alias.phar.'
        );
    }

    /**
     * Make sure we can get the size of the metadata.
     */
    public function testGetMetadataSize()
    {
        $this->assertEquals(
            34,
            $this->manifest->getMetadataSize(),
            'The size of the metadata should be 30.'
        );

        $manifest = new Manifest(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertEquals(
            0,
            $manifest->getMetadataSize(),
            'The size of the metadata in no-alias.phar should be 0.'
        );
    }

    /**
     * Make sure we can get back the file reader.
     */
    public function testGetReader()
    {
        $this->assertSame(
            $this->reader,
            $this->manifest->getReader(),
            'The reader should be returned.'
        );
    }

    /**
     * Make sure we can get the size of the manifest.
     */
    public function testGetSize()
    {
        $this->assertEquals(
            166,
            $this->manifest->getSize(),
            'The manifest size should be 166.'
        );
    }

    /**
     * Creates a new instance of `Manifest` for testing.
     */
    protected function setUp()
    {
        $this->file = realpath(__DIR__ . '/../../../../../res/example.phar');
        $this->reader = new Reader($this->file);
        $this->manifest = new Manifest($this->reader);
    }
}
