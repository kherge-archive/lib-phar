<?php

namespace Phine\Phar\Tests;

use ArrayIterator;
use Phine\Phar\Builder;
use Phine\Phar\Builder\Subject\AbstractSubject;
use Phine\Phar\Test\Phar;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link BuilderTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuilderTest extends TestCase
{
    /**
     * The builder instance being tested.
     *
     * @var Builder
     */
    private $builder;

    /**
     * The test PHP archive file path.
     *
     * @var string
     */
    private $file;

    /**
     * The test PHP archive instance.
     *
     * @var Phar
     */
    private $phar;

    /**
     * Make sure that we can set the PHP archive and default subjects.
     */
    public function testConstruct()
    {
        $this->assertSame(
            $this->phar,
            get($this->builder, 'phar'),
            'Make sure the Phar instance is set.'
        );

        $subjects = array(
            Builder::ADD_DIR => 'Phine\\Phar\\Builder\\Subject\\AddDirectory',
            Builder::ADD_FILE => 'Phine\\Phar\\Builder\\Subject\\AddFile',
            Builder::ADD_STRING => 'Phine\\Phar\\Builder\\Subject\\AddString',
            Builder::BUILD_DIR => 'Phine\\Phar\\Builder\\Subject\\BuildDirectory',
            Builder::BUILD_ITERATOR => 'Phine\\Phar\\Builder\\Subject\\BuildIterator',
        );

        foreach ($subjects as $id => $class) {
            $this->assertInstanceOf(
                $class,
                $this->builder->getSubject($id),
                'Make sure the expected subject class is registered to the right identifier.'
            );
        }
    }

    /**
     * Make sure that the ADD_DIR event is fired properly.
     */
    public function testAddEmptyDir()
    {
        $this->builder->addEmptyDir('test');

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::ADD_DIR);
        $arguments = $subject->getArguments();

        $this->assertEquals(
            'test',
            $arguments['name'],
            'Make sure the directory name is passed on.'
        );
    }

    /**
     * Make sure that the ADD_FILE event is fired properly.
     */
    public function testAddFile()
    {
        $this->builder->addFile('/path/to/test.php', 'test.php');

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::ADD_FILE);
        $arguments = $subject->getArguments();

        $this->assertEquals(
            '/path/to/test.php',
            $arguments['file'],
            'Make sure the file path is passed on.'
        );

        $this->assertEquals(
            'test.php',
            $arguments['local'],
            'Make sure the local path is passed on.'
        );
    }

    /**
     * Make sure that the ADD_STRING event is fired properly.
     */
    public function testAddFromString()
    {
        $this->builder->addFromString('test.php', 'contents');

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::ADD_STRING);
        $arguments = $subject->getArguments();

        $this->assertEquals(
            'test.php',
            $arguments['local'],
            'Make sure the local path is passed on.'
        );

        $this->assertEquals(
            'contents',
            $arguments['contents'],
            'Make sure the contents are passed on.'
        );
    }

    /**
     * Make sure that the BUILD_DIR event is fired properly.
     */
    public function testBuildFromDirectory()
    {
        $this->builder->buildFromDirectory('/path/to/dir', '/regex/');

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::BUILD_DIR);
        $arguments = $subject->getArguments();

        $this->assertEquals(
            '/path/to/dir',
            $arguments['dir'],
            'Make sure the directory path is passed on.'
        );

        $this->assertEquals(
            '/regex/',
            $arguments['regex'],
            'Make sure the regular expression is passed on.'
        );
    }

    /**
     * Make sure that the BUILD_ITERATOR event is fired properly.
     */
    public function testBuildFromIterator()
    {
        $iterator = new ArrayIterator(array());

        $this->builder->buildFromIterator($iterator, '/path/to/base');

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::BUILD_ITERATOR);
        $arguments = $subject->getArguments();

        $this->assertSame(
            $iterator,
            $arguments['iterator'],
            'Make sure the iterator is passed on.'
        );

        $this->assertEquals(
            '/path/to/base',
            $arguments['base'],
            'Make sure the base directory path is passed on.'
        );
    }

    /**
     * Make sure we can create a new PHP archive and Builder instance.
     */
    public function testCreate()
    {
        $this->assertInstanceOf(
            'Phine\\Phar\\Builder',
            Builder::create($this->file),
            'Make sure we get a new builder instance.'
        );
    }

    /**
     * Make sure that we can get back the Phar instance.
     */
    public function testGetPhar()
    {
        $this->assertSame(
            $this->phar,
            $this->builder->getPhar(),
            'Make sure we get back the same Phar instance we put in.'
        );
    }

    /**
     * Creates a new PHP archive and {@link Builder} instance for testing.
     */
    protected function setUp()
    {
        unlink($this->file = tempnam(sys_get_temp_dir(), 'phar'));

        $this->file .= '.phar';

        $this->phar = new Phar($this->file);
        $this->builder = new Builder($this->phar);
    }

    /**
     * Cleans up the temporary PHP archive.
     */
    protected function tearDown()
    {
        $this->phar = null;

        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }
}
