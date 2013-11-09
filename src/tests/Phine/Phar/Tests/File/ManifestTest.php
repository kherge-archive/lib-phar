<?php

namespace Phine\Phar\Tests\File;

use Phine\Phar\File\Manifest;
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
        $this->assertEquals(
            94,
            Manifest::findOffset(new Reader($this->file)),
            'The offset returned should be 94.'
        );

        $this->assertNull(
            Manifest::findOffset(new Reader(__FILE__)),
            'No offset should be found in non-archive files.'
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
        $this->assertEquals(
            array(
                array(
                    'crc32'=> 4091535927,
                    'flags' => 0,
                    'metadata' => array(
                        'size' => 0,
                    ),
                    'name' => array(
                        'data' => 'bin/main',
                        'size' => 8,
                    ),
                    'offset' => 264,
                    'size' => array(
                        'compressed' => 77,
                        'uncompressed' => 77,
                    ),
                    'time' => 1383980172,
                ),
                array(
                    'crc32'=> 2063568359,
                    'flags' => 0,
                    'metadata' => array(
                        'data' => array(
                            'rand' => 1317613458
                        ),
                        'size' => 30,
                    ),
                    'name' => array(
                        'data' => 'src/Put.php',
                        'size' => 11,
                    ),
                    'offset' => 341,
                    'size' => array(
                        'compressed' => 104,
                        'uncompressed' => 104,
                    ),
                    'time' => 1383980172,
                ),
            ),
            $this->manifest->getFileList(),
            'There should be two files with the appropriate details.'
        );
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
        $this->file = realpath(__DIR__ . '/../../../../../../res/example.phar');
        $this->reader = new Reader($this->file);
        $this->manifest = new Manifest($this->reader);
    }
}
