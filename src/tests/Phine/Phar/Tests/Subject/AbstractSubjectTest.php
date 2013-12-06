<?php

namespace Phine\Phar\Tests\Subject;

use Phine\Observer\Exception\ReasonException;
use Phine\Phar\Builder;
use Phine\Phar\Subject\Arguments;
use Phine\Phar\Test\Subject;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link AbstractSubject} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AbstractSubjectTest extends TestCase
{
    /**
     * The test builder for the subject.
     *
     * @var Builder
     */
    private $builder;

    /**
     * The abstract subject being tested.
     *
     * @var Subject
     */
    private $subject;

    /**
     * Make sure that we can retrieve the method arguments.
     */
    public function testGetArguments()
    {
        $arguments = new Arguments(array());

        Property::set($this->subject, 'arguments', $arguments);

        $this->assertSame(
            $arguments,
            $this->subject->getArguments(),
            'Make sure we get back the arguments.'
        );
    }

    /**
     * Make sure the last step is called after a successful update.
     */
    public function testNotifyObservers()
    {
        $this->subject->notifyObservers();

        $this->assertTrue(
            $this->subject->done,
            'Make sure the last step method is called.'
        );
    }

    /**
     * Make sure that we can set the new method arguments.
     */
    public function testSetArguments()
    {
        $arguments = new Arguments(array());

        $this->subject->setArguments($arguments);

        $this->assertSame(
            $arguments,
            Property::get($this->subject, 'arguments'),
            'Make sure the new arguments are set.'
        );

        Property::set($this->subject, 'updating', true);

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\BuilderException',
            'The subject cannot be modified during an update.'
        );

        $this->subject->setArguments($arguments);
    }

    /**
     * Creates an instance of {@link Subject} for testing.
     */
    protected function setUp()
    {
        $this->builder = $this
            ->getMockBuilder('Phine\\Phar\\Builder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new Subject($this->builder);
    }
}
