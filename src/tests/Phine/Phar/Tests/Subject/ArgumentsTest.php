<?php

namespace Phine\Phar\Tests\Builder;

use DateTime;
use Phine\Phar\Subject\Arguments;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link ArgumentsTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArgumentsTest extends TestCase
{
    /**
     * The arguments instance to be tested.
     *
     * @var Arguments
     */
    private $arguments;

    /**
     * The overriding argument values.
     *
     * @var array
     */
    private $override;

    /**
     * The original argument values.
     *
     * @var array
     */
    private $values;

    /**
     * Make sure that we get the current values as an iterator.
     */
    public function testGetIterator()
    {
        $expected = array(
            'date_time' => $this->override['date_time'],
            'null' => $this->values['null'],
            'random' => $this->values['random'],
            'string' => $this->values['string']
        );

        foreach ($this->arguments as $key => $value) {
            $this->assertSame(
                $expected[$key],
                $value,
                'Make sure we get the expected value.'
            );
        }
    }

    /**
     * Make sure that we can get the original value after it is overridden.
     */
    public function testGetOriginalValue()
    {
        $this->assertSame(
            $this->values['date_time'],
            $this->arguments->getOriginalValue('date_time'),
            'Make sure we get the original DateTime object back.'
        );
    }

    /**
     * Make sure that undefined arguments thrown an exception.
     */
    public function testGetOriginalValueUndefined()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\BuilderException',
            'The argument "test" is not defined.'
        );

        $this->arguments->getOriginalValue('test');
    }

    /**
     * Make sure that we can check if an argument is defined.
     */
    public function testOffsetExists()
    {
        $this->assertFalse(
            isset($this->arguments['test']),
            'Make sure the "test" argument is undefined.'
        );

        $this->assertTrue(
            isset($this->arguments['null']),
            'Make sure the "null" argument is defined.'
        );
    }

    /**
     * Make sure that we can get the current argument value.
     */
    public function testOffsetGet()
    {
        $this->assertSame(
            $this->override['date_time'],
            $this->arguments['date_time'],
            'Make sure we get the override value.'
        );

        $this->assertSame(
            $this->values['random'],
            $this->arguments['random'],
            'Make sure we get the original value.'
        );
    }

    /**
     * Make sure that an undefined argument throws an exception.
     */
    public function testOffsetGetUndefined()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\BuilderException',
            'The argument "test" is not defined.'
        );

        $this->arguments['test'];
    }

    /**
     * Make sure that we can set the override value.
     */
    public function testOffsetSet()
    {
        $rand = rand();

        $this->arguments['random'] = $rand;

        $original = Property::get($this->arguments, 'original');
        $override = Property::get($this->arguments, 'override');

        $this->assertNotSame(
            $rand,
            $original['random'],
            'Make sure the original value is unchanged.'
        );

        $this->assertSame(
            $rand,
            $override['random'],
            'Make sure the override is set.'
        );
    }

    /**
     * Make sure that we cannot define a new argument.
     */
    public function testOffsetSetUndefined()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\BuilderException',
            'The argument "test" is not defined.'
        );

        $this->arguments['test'] = 123;
    }

    /**
     * Make sure that we can restore the original argument value.
     */
    public function testOffsetUnset()
    {
        unset($this->arguments['date_time']);

        $this->assertSame(
            array(),
            Property::get($this->arguments, 'override'),
            'Make sure we can unset the override value.'
        );
    }

    /**
     * Make sure that we cannot unset an undefined argument.
     */
    public function testOffsetUnsetUndefined()
    {
        $this->setExpectedException(
            'Phine\\Phar\\Exception\\BuilderException',
            'The argument "test" is not defined.'
        );

        unset($this->arguments['test']);
    }

    /**
     * Creates a new instance of {@link Arguments} for testing.
     */
    protected function setUp()
    {
        $this->override = array(
            'date_time' => new DateTime()
        );

        $this->values = array(
            'date_time' => new DateTime(),
            'null' => null,
            'random' => rand(),
            'string' => 'This is a test string.'
        );

        $this->arguments = new Arguments($this->values);

        Property::set(
            $this->arguments,
            'override',
            $this->override
        );
    }
}
