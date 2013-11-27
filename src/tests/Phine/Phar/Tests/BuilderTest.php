<?php

namespace Phine\Phar\Tests;

use ArrayIterator;
use Phine\Phar\Builder;
use Phine\Phar\Subject\AbstractSubject;
use Phine\Phar\Test\Observer;
use Phine\Test\Property;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
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
     * The test PHP archive instance.
     *
     * @var MockObject
     */
    private $phar;

    /**
     * Make sure that we can set the PHP archive and default subjects.
     */
    public function testConstruct()
    {
        $this->assertSame(
            $this->phar,
            Property::get($this->builder, 'phar'),
            'Make sure the Phar instance is set.'
        );

        $subjects = array(
            Builder::ADD_DIR => 'Phine\\Phar\\Subject\\Builder\\AddDirectory',
            Builder::ADD_FILE => 'Phine\\Phar\\Subject\\Builder\\AddFile',
            Builder::ADD_STRING => 'Phine\\Phar\\Subject\\Builder\\AddString',
            Builder::BUILD_DIR => 'Phine\\Phar\\Subject\\Builder\\BuildDirectory',
            Builder::BUILD_ITERATOR => 'Phine\\Phar\\Subject\\Builder\\BuildIterator',
            Builder::SET_STUB => 'Phine\\Phar\\Subject\\Builder\\SetStub'
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
        $this
            ->phar
            ->expects($this->once())
            ->method('buildFromDirectory')
            ->will($this->returnValue('returned'));

        $this->assertEquals(
            'returned',
            $this->builder->buildFromDirectory('/path/to/dir', '/regex/'),
            'The value should be returned.'
        );

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

        $this
            ->phar
            ->expects($this->once())
            ->method('buildFromIterator')
            ->will($this->returnValue('returned'));

        $this->assertEquals(
            'returned',
            $this->builder->buildFromIterator($iterator, '/path/to/base'),
            'The value should be returned.'
        );

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
        unlink($tmp = tempnam(sys_get_temp_dir(), 'phar'));

        $this->assertInstanceOf(
            'Phine\\Phar\\Builder',
            Builder::create($tmp . '.phar'),
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
     * Make sure the observer registration shortcut works.
     */
    public function testObserve()
    {
        $observer = new Observer();

        $this->builder->observe(Builder::ADD_DIR, $observer);

        $this->assertTrue(
            $this->builder->getSubject(Builder::ADD_DIR)->hasObserver($observer),
            'The observer should be registered to the correct subject.'
        );
    }

    /**
     * Make sure that the SET_STUB event is fired properly.
     */
    public function testSetStub()
    {
        $stub = '<?php __HALT__COMPILER();';

        $this->builder->setStub($stub);

        /** @var AbstractSubject $subject */
        $subject = $this->builder->getSubject(Builder::SET_STUB);
        $arguments = $subject->getArguments();

        $this->assertEquals(
            $stub,
            $arguments['stub'],
            'Make sure the stub is passed on.'
        );
    }

    /**
     * Creates a new PHP archive and {@link Builder} instance for testing.
     */
    protected function setUp()
    {
        $this->phar = $this
            ->getMockBuilder('Phar')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new Builder($this->phar);
    }
}
