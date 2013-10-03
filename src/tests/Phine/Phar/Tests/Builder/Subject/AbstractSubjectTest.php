<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Observer\Exception\ReasonException;
use Phine\Phar\Builder;
use Phine\Phar\Builder\Arguments;
use Phine\Phar\Test\Observer;
use Phine\Phar\Test\Phar;
use Phine\Phar\Test\Subject;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link AbstractSubjectTest} class.
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

        set($this->subject, 'arguments', $arguments);

        $this->assertSame(
            $arguments,
            $this->subject->getArguments(),
            'Make sure we get back the arguments.'
        );
    }

    /**
     * Make sure we can check if an update is in progress.
     */
    public function testIsUpdating()
    {
        $this->assertFalse(
            $this->subject->isUpdating(),
            'Make sure that no update is in progress.'
        );

        set($this->subject, 'updating', true);

        $this->assertTrue(
            $this->subject->isUpdating(),
            'Make sure that an update is in progress.'
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

        $this->assertFalse(
            get($this->subject, 'updating'),
            'Make sure the updating flag is reset.'
        );
    }

    /**
     * Make sure the updating flag is reset on interrupt.
     */
    public function testNotifyObserversInterrupted()
    {
        $this->subject->registerObserver(new Observer());

        try {
            $this->subject->notifyObservers();
        } catch (ReasonException $exception) {
        }

        $this->assertFalse(
            get($this->subject, 'updating'),
            'Make sure the updating flag is reset.'
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
            get($this->subject, 'arguments'),
            'Make sure the new arguments are set.'
        );

        set($this->subject, 'updating', true);

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
        $this->builder = new Builder(new Phar('test.phar'));
        $this->subject = new Subject($this->builder);
    }
}