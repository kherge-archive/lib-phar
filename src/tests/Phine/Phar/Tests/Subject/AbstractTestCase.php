<?php

namespace Phine\Phar\Tests\Subject;

use Phar;
use Phine\Phar\Builder;
use Phine\Phar\Subject\Arguments;
use Phine\Phar\Subject\AbstractSubject;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Simplifies the process for writing test cases for builder event subjects.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * The subject ID being tested.
     */
    const SUBJECT_ID = 'REPLACE ME';

    /**
     * The builder for the subject.
     *
     * @var Builder
     */
    protected $builder;

    /**
     * The test PHP archive instance.
     *
     * @var MockObject|Phar
     */
    protected $phar;

    /**
     * The subject being tested.
     *
     * @var AbstractSubject
     */
    protected $subject;

    /**
     * Sets the arguments and and updates the subject's observers.
     *
     * @param array $args The method arguments.
     *
     * @return mixed Any resulting value.
     */
    protected function invokeSubject(array $args)
    {
        $this->subject->setArguments(new Arguments($args));

        return $this->subject->notifyObservers();
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
        $this->subject = $this->builder->getSubject(static::SUBJECT_ID);
    }
}
