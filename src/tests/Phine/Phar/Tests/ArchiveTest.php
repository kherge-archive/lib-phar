<?php

namespace Phine\Phar\Tests;

use Phine\Phar\Archive;
use Phine\Phar\File\Reader;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Archive} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArchiveTest extends TestCase
{
    /**
     * The archive file being used for testing.
     *
     * @var string
     */
    private $file;

    /**
     * The archive file reader instance being tested.
     *
     * @var Archive
     */
    private $archive;

    /**
     * The file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * Make sure that the offset is found and the reader is set.
     *
     * If the offset of the data is not provided, make sure that it is located
     * in valid archives. If the data offset could not be found, an exception
     * should be thrown. If the offset is provided, make sure that it is set
     * along with the archive file reader.
     */
    public function testConstruct()
    {
        $this->assertSame(
            $this->reader,
            Property::get($this->archive, 'reader'),
            'The file reader should be set.'
        );

        $this->assertEquals(
            94,
            Property::get($this->archive, 'offset'),
            'The data offset should be 94.'
        );

        $manifest = new Archive($this->reader, 123);

        $this->assertEquals(
            123,
            Property::get($manifest, 'offset'),
            'The data offset should be 123.'
        );

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\ArchiveException',
            sprintf(
                'The data offset could not be found in the PHP archive file "%s".',
                __FILE__
            )
        );

        new Archive(new Reader(__FILE__));
    }

    /**
     * Make sure that we can create a new instance of the file and archive reader.
     */
    public function testCreate()
    {
        $archive = Archive::create($this->file, 1234);

        $this->assertInstanceOf(
            'Phine\\Phar\\Archive',
            $archive,
            'An instance of Archive should be returned.'
        );

        $this->assertEquals(
            1234,
            Property::get($archive, 'offset'),
            'The data offset should be set.'
        );

        $this->assertNotNull(
            Property::get($archive, 'reader'),
            'The file reader should be set.'
        );
    }

    /**
     * Make sure that we can find the data offset, if possible.
     */
    public function testFindOffset()
    {
        // using a custom stub
        $this->assertEquals(
            94,
            Archive::findOffset(new Reader($this->file)),
            'The offset returned should be 94.'
        );

        $this->assertNull(
            Archive::findOffset(new Reader(__FILE__)),
            'No offset should be found in non-archive files.'
        );

        // using the default stub
        $this->assertEquals(
            6683,
            Archive::findOffset(new Reader(dirname($this->file) . '/default.phar')),
            'The offset returned should be 6683.'
        );
    }

    /**
     * Make sure we can read the stream alias of an archive.
     */
    public function testGetAlias()
    {
        $this->assertEquals(
            'test.phar',
            $this->archive->getAlias(),
            'The alias "test.phar" should be returned.'
        );

        $manifest = new Archive(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertNull(
            $manifest->getAlias(),
            'There should be no alias returned.'
        );
    }

    /**
     * Make sure we can get the size of the stream alias.
     */
    public function testGetAliasSize()
    {
        $this->assertEquals(
            9,
            $this->archive->getAliasSize(),
            'The size of the alias should be 9.'
        );

        $manifest = new Archive(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertEquals(
            0,
            $manifest->getAliasSize(),
            'The size of the alias should be 0.'
        );
    }

    /**
     * Make sure we can read the file format version of an archive.
     */
    public function testGetApiVersion()
    {
        $this->assertEquals(
            '1.1.0',
            $this->archive->getApiVersion(),
            'The version "1.1.0" should be returned.'
        );
    }

    /**
     * Make sure we can retrieve the list of manifest entries.
     *
     * The list returned should be an array of Entry instances. Each instance
     * of the list contains detailed information about the file in the archive.
     * This information can be used to extract files.
     */
    public function testGetEntries()
    {
        $files = $this->archive->getEntries();

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
     * Make sure we can retrieve the entry count for an archive.
     */
    public function testGetEntryCount()
    {
        $this->assertEquals(
            2,
            $this->archive->getEntryCount(),
            'There should be 2 files in example.phar.'
        );

        $manifest = new Archive(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertEquals(
            0,
            $manifest->getEntryCount(),
            'There should be no files in no-alias.phar.'
        );
    }

    /**
     * Make sure we can get the archive's global flags.
     */
    public function testGetGlobalFlags()
    {
        $this->assertEquals(
            0,
            $this->archive->getGlobalFlags(),
            'The global flags should be returned.'
        );
    }

    /**
     * Make sure we can get the data offset for the archive.
     */
    public function testGetOffset()
    {
        $this->assertEquals(
            94,
            $this->archive->getOffset(),
            'The offset for the archive should be 94.'
        );
    }

    /**
     * Make sure we can get the size of the manifest for the archive.
     */
    public function testGetManifestSize()
    {
        $this->assertEquals(
            166,
            $this->archive->getManifestSize(),
            'The archive size should be 166.'
        );
    }

    /**
     * Make sure we can retrieve the global metadata.
     */
    public function testGetMetadata()
    {
        $this->assertEquals(
            array(
                'who' => 'It was me!'
            ),
            $this->archive->getMetadata(),
            'The metadata should be returned.'
        );

        $manifest = new Archive(
            new Reader(dirname($this->file) . '/no-alias.phar')
        );

        $this->assertNull(
            $manifest->getMetadata(),
            'There should be no metadata in no-alias.phar.'
        );
    }

    /**
     * Make sure we can get the size of the global metadata.
     */
    public function testGetMetadataSize()
    {
        $this->assertEquals(
            34,
            $this->archive->getMetadataSize(),
            'The size of the metadata should be 30.'
        );

        $manifest = new Archive(
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
            $this->archive->getReader(),
            'The reader should be returned.'
        );
    }

    /**
     * Make sure we can check if an archive has metadata.
     */
    public function testHasMetadata()
    {
        $this->assertTrue(
            $this->archive->hasMetadata(),
            'The archive should have metadata.'
        );

        $reader = new Reader(dirname($this->file) . '/default.phar');
        $archive = new Archive($reader);

        $this->assertFalse(
            $archive->hasMetadata(),
            'The archive should not have metadata.'
        );
    }

    /**
     * Creates a new instance of `Archive` for testing.
     */
    protected function setUp()
    {
        $this->file = realpath(__DIR__ . '/../../../../../res/example.phar');
        $this->reader = new Reader($this->file);
        $this->archive = new Archive($this->reader);
    }
}
