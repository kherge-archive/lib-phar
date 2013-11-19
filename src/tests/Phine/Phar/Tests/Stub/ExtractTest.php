<?php

namespace Phine\Phar\Tests\Stub;

use Exception;
use Phine\Phar\Stub\Extract;
use Phine\Test\Method;
use Phine\Test\Property;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;
use RuntimeException;

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
     * The extract instance being tested.
     *
     * @var Extract
     */
    private $extract;

    /**
     * The temporary directory path.
     *
     * @var string
     */
    private $dir;

    /**
     * The archive file being used for testing.
     *
     * @var string
     */
    private $file;

    /**
     * The temporary path manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure the file is set and the compression test results are saved.
     */
    public function testConstruct()
    {
        $this->assertEquals(
            $this->file,
            Property::get($this->extract, 'file'),
            'The file path should be set.'
        );

        $this->assertEquals(
            array(
                'bzip2' => function_exists('bzdecompress'),
                'gzip' => function_exists('gzinflate')
            ),
            Property::get($this->extract, 'compression'),
            'The compression test results should be set.'
        );

        $this->setExpectedException(
            'InvalidArgumentException',
            'The path "/does/not/exist" is not a file or does not exist.'
        );

        new Extract('/does/not/exist');
    }

    /**
     * Make sure an open file handle is closed.
     */
    public function testDestruct()
    {
        Property::set($this->extract, 'handle', fopen('php://memory', 'w'));

        $handle = Property::get($this->extract, 'handle');

        $this->extract = null;

        $this->setExpectedException(
            'PHPUnit_Framework_Error_Warning',
            'is not a valid stream'
        );

        fwrite($handle, 'test');
    }

    /**
     * Make sure that file extraction errors are properly handled.
     */
    public function testExtractFileErrors()
    {
        $extract = $this->extract;
        $temp = $this->temp;

        Property::set(
            $extract,
            'compression',
            array(
                'bzip2' => false,
                'gzip' => true,
            )
        );

        $this->expectException(
            'RuntimeException',
            'The "bz2" extension is required to decompress "bzip2.php".',
            function () use ($extract, $temp) {
                $extract->to($temp->createDir());
            }
        );

        Property::set(
            $extract,
            'compression',
            array(
                'bzip2' => true,
                'gzip' => false,
            )
        );

        $this->expectException(
            'RuntimeException',
            'The "zlib" extension is required to decompress "gzip.php".',
            function () use ($extract, $temp) {
                $extract->to($temp->createDir());
            }
        );
    }

    /**
     * Make sure a new instance is returned for the file.
     */
    public function testFrom()
    {
        $extract = Extract::from($this->file);

        $this->assertInstanceOf(
            'Phine\\Phar\\Stub\\Extract',
            $extract,
            'An instance of Extract should be returned.'
        );

        $this->assertEquals(
            $this->file,
            Property::get($extract, 'file'),
            'The file path should be set.'
        );
    }

    /**
     * Make sure we can support a variety of stub formats, or none at all.
     */
    public function testFindOffset()
    {
        // using a custom stub
        $extract = new Extract(
            realpath(__DIR__ . '/../../../../../../res/example.phar')
        );

        $this->assertEquals(
            94,
            Method::invoke($extract, 'findOffset'),
            'The offset returned should be 94.'
        );

        $extract = new Extract(__FILE__);

        $this->assertNull(
            Method::invoke($extract, 'findOffset'),
            'No offset should be found in non-archive files.'
        );

        // using the default stub
        $extract = new Extract(
            realpath(__DIR__ . '/../../../../../../res/default.phar')
        );

        $this->assertEquals(
            6683,
            Method::invoke($extract, 'findOffset'),
            'The offset returned should be 6683.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't open the file.
     */
    public function testGetHandleError()
    {
        Property::set($this->extract, 'file', '/does/not/exist');

        $this->setExpectedException(
            'RuntimeException',
            'failed to open stream'
        );

        Method::invoke($this->extract, 'getHandle');
    }

    /**
     * Make sure we can get a semi-compacted version of the class's source.
     */
    public function testGetSource()
    {
        $this->assertRegExp(
            '/^final class Extract/',
            Extract::getSource(),
            'The source code should be returned.'
        );
    }

    /**
     * Make sure an exception is thrown if we can't get the position.
     */
    public function testGetPositionError()
    {
        Property::set($this->extract, 'handle', 'test');

        try {
            Method::invoke($this->extract, 'getPosition');
        } catch (RuntimeException $exception) {
        }

        Property::set($this->extract, 'handle', null);

        $this->setExpectedException(
            'RuntimeException',
            'expects parameter 1 to be resource, string given'
        );

        /** @noinspection PhpUndefinedVariableInspection */
        throw $exception;
    }

    /**
     * Make sure that error handling for reading is properly done.
     */
    public function testReadError()
    {
        $extract = $this->extract;

        $this->expectException(
            'RuntimeException',
            'fread(): Length parameter must be greater than 0',
            function () use ($extract) {
                Method::invoke($extract, 'read', -4);
            }
        );

        $size = filesize($this->file);
        $up = $size + 1;

        $this->expectException(
            'RuntimeException',
            "Only read $size bytes of $up from \"{$this->file}\".",
            function () use ($extract, $up) {
                Method::invoke($extract, 'read', $up);
            }
        );
    }

    /**
     * Make sure we can extract the files of an archive.
     *
     * If a directory path is not provided, make sure that the standard
     * temporary directory path (as used by the default stub's Extract class)
     * is used and returned. Also, regardless of what path is used, the method
     * should not re-extract the contents of the archive if a checksum file
     * exists in the target extraction path.
     */
    public function testTo()
    {
        $this->assertEquals(
            $this->dir,
            $this->extract->to($this->dir),
            'The directory path should be returned.'
        );

        $this->assertEquals(
            <<<FILE
<?php

echo "This file was compressed using bzip2.\\n";
FILE
            ,
            file_get_contents($this->dir . '/bzip2.php'),
            'The Put.php file should have been extracted.'
        );

        $this->assertEquals(
            <<<FILE
<?php

echo "This file was compressed using gzip.\\n";
FILE
            ,
            file_get_contents($this->dir . '/gzip.php'),
            'The Put.php file should have been extracted.'
        );

        $this->assertFileExists(
            $this->dir . '/' . md5_file($this->file),
            'The checksum file should have been created.'
        );

        unlink($this->dir . '/bzip2.php');

        $this->extract->to($this->dir);

        $this->assertFileNotExists(
            $this->dir . '/bzip2.php',
            'The archive should not be re-extracted.'
        );

        $this->assertEquals(
            sprintf(
                '%s%spharextract%s%s',
                sys_get_temp_dir(),
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
                basename($this->file, '.phar')
            ),
            $this->extract->to(),
            'The standard temporary directory path should be used.'
        );
    }

    /**
     * Creates a new instance of `Extract` for testing.
     */
    protected function setUp()
    {
        $this->file = realpath(__DIR__ . '/../../../../../../res/compressed.phar');
        $this->temp = new Temp();
        $this->dir = $this->temp->createDir();
        $this->extract = new Extract($this->file);
    }

    /**
     * Cleans up the temporary directory path.
     */
    protected function tearDown()
    {
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
