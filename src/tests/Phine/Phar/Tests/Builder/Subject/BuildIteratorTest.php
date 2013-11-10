<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests the methods in the {@link BuildIterator} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildIteratorTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::BUILD_ITERATOR;

    /**
     * Make sure that we can build using an iterator.
     */
    public function testDoLastStep()
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__)
        );

        $this
            ->phar
            ->expects($this->once())
            ->method('buildFromIterator')
            ->with(
                $this->equalTo($iterator),
                $this->equalTo(__DIR__)
            );

        $this->invokeSubject(
            array(
                'iterator' => $iterator,
                'base' => __DIR__
            )
        );
    }
}
