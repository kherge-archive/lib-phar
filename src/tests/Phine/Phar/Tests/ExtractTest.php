<?php

namespace Phine\Phar\Tests;

use Exception;
use Phine\Phar\Extract;
use Phine\Phar\Archive;
use Phine\Phar\Manifest\Entry;
use Phine\Phar\File\Reader;
use Phine\Test\Property;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Performs unit tests on the `Extract` class.
 *
 * @see Extract
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ExtractTest extends TestCase
{
    /**
     * The temporary directory path.
     *
     * @var string
     */
    private $dir;

    /**
     * The extract instance being tested.
     *
     * @var Extract
     */
    private $extract;

    /**
     * The archive file being used for testing.
     *
     * @var string
     */
    private $file;

    /**
     * The archive archive.
     *
     * @var Archive
     */
    private $manifest;

    /**
     * The archive file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The temporary path manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure that the archive is set.
     */
    public function testConstruct()
    {
        $this->assertSame(
            $this->manifest,
            Property::get($this->extract, 'archive'),
            'The archive should be set.'
        );
    }

    /**
     * Make sure we can read the contents of a file.
     *
     * If the file is compressed, it must be decompressed before it is returned.
     * If the compression algorithm used does not have the supporting extension
     * available, an exception should be thrown.
     */
    public function testExtractFile()
    {
        $file = realpath(__DIR__ . '/../../../../../res/compressed.phar');
        $reader = new Reader($file);
        $manifest = new Archive($reader);

        $files = $manifest->getEntries();

        $this->assertEquals(
            <<<CONTENTS
<?php

echo "This file was compressed using bzip2.\\n";
CONTENTS
            ,
            Extract::extractFile($files[0]),
            'The decompressed contents should be returned.'
        );

        $this->assertEquals(
            <<<CONTENTS
<?php

echo "This file was compressed using gzip.\\n";
CONTENTS
            ,
            Extract::extractFile($files[1]),
            'The decompressed contents should be returned.'
        );

        $this->assertEquals(
            array(
                'bzip2' => function_exists('bzdecompress'),
                'gzip' => function_exists('gzinflate')
            ),
            Property::get('Phine\\Phar\\Extract', 'compression'),
            'The compression function checks should be done.'
        );

        Property::set(
            'Phine\\Phar\\Extract',
            'compression',
            array(
                'bzip2' => false,
                'gzip' => true,
            )
        );

        $this->expectException(
            'Phine\\Phar\\Exception\\FileException',
            'The "bz2" extension is required to decompress "bzip2.php".',
            function () use ($files) {
                Extract::extractFile($files[0]);
            }
        );

        Property::set(
            'Phine\\Phar\\Extract',
            'compression',
            array(
                'bzip2' => true,
                'gzip' => false,
            )
        );

        $this->expectException(
            'Phine\\Phar\\Exception\\FileException',
            'The "zlib" extension is required to decompress "gzip.php".',
            function () use ($files) {
                Extract::extractFile($files[1]);
            }
        );
    }

    /**
     * Make sure we can extract the files of an archive.
     *
     * Also make sure that we can be selective about the files we choose. A
     * callable should be accepted as a filter, which will allow for complete
     * control over which files get extracted. If the directory path for the
     * file could not be created, an exception should be thrown.
     */
    public function testExtractTo()
    {
        $this->extract->extractTo($this->dir);

        $this->assertEquals(
            <<<FILE
<?php



require __DIR__ . '/../src/Put.php';



Put::line('Hello, world!');

FILE
            ,
            file_get_contents($this->dir . '/bin/main'),
            'The Put.php file should have been extracted.'
        );

        $this->assertEquals(
            <<<FILE
<?php

class Put
{
    public static function line(\$message)
    {
        echo \$message, "\\n";
    }
}

FILE
            ,
            file_get_contents($this->dir . '/src/Put.php'),
            'The Put.php file should have been extracted.'
        );

        unlink($this->dir . '/bin/main');
        unlink($this->dir . '/src/Put.php');

        $want = 'bin' . DIRECTORY_SEPARATOR . 'main';

        $this->extract->extractTo(
            $this->dir,
            function ($file) use ($want) {
                /** @var Entry $file */
                if ($file->getName() !== $want) {
                    return true;
                }

                return false;
            }
        );

        $this->assertFileExists(
            $this->dir . '/bin/main',
            'The main script should have been extracted.'
        );

        $this->assertFileNotExists(
            'The class script should not have been extracted.'
        );

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\FileException',
            'mkdir():'
        );

        $this->extract->extractTo('/does/not/exist');
    }

    /**
     * Make sure we can get back the archive.
     */
    public function testGetManifest()
    {
        $this->assertSame(
            $this->manifest,
            $this->extract->getArchive(),
            'The archive should be returned.'
        );
    }

    /**
     * Creates a new instance of `Extract` for testing.
     */
    protected function setUp()
    {
        $this->file = realpath(__DIR__ . '/../../../../../res/example.phar');
        $this->temp = new Temp();
        $this->dir = $this->temp->createDir();
        $this->reader = new Reader($this->file);
        $this->manifest = new Archive($this->reader);
        $this->extract = new Extract($this->manifest);
    }

    /**
     * Cleans up the temporary directory path.
     */
    protected function tearDown()
    {
        $this->extract = null;
        $this->reader = null;

        $this->temp->purgePaths();
    }

    /**
     * Performs an exception assertion without requiring the test case to end.
     *
     * Normally, PHPUnit requires that the test case end before a check is done
     * to see if an exception was thrown. This method is a workaround that will
     * allow the test case to continue after the expected exception is thrown.
     *
     * @param string   $class   The expected class.
     * @param string   $message The expected message.
     * @param callable $test    The test to perform.
     */
    private function expectException($class, $message, $test)
    {
        $exception = null;

        try {
            $test($this);
        } catch (Exception $exception) {
            $this->assertInstanceOf($class, $exception);
            $this->assertEquals($message, $exception->getMessage());
        }

        $this->assertTrue(
            isset($exception),
            'An exception should have been thrown.'
        );
    }
}
