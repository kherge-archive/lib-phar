<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests the methods in the {@link BuildIteratorTest} class.
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

        $this->invokeSubject(
            array(
                'iterator' => $iterator,
                'base' => __DIR__
            )
        );

        $args = $this->subject->getArguments();

        $this->assertSame(
            $iterator,
            $args['iterator'],
            'Make sure the iterator is provided.'
        );

        $this->assertEquals(
            __DIR__,
            $args['base'],
            'Make sure the base directory path is provided.'
        );
    }
}
