<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link AddFileTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddFileTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::ADD_FILE;

    /**
     * Make sure that we can add a file.
     */
    public function testDoLastStep()
    {
        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => null
            )
        );

        $args = $this->subject->getArguments();

        $this->assertEquals(
            __FILE__,
            $args['file'],
            'Make sure the file name is provided.'
        );

        $this->assertNull(
            $args['local'],
            'Make sure no local name is provided.'
        );

        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => 'test.php'
            )
        );

        $args = $this->subject->getArguments();

        $this->assertEquals(
            __FILE__,
            $args['file'],
            'Make sure the file name is provided.'
        );

        $this->assertEquals(
            'test.php',
            $args['local'],
            'Make sure the local name is provided.'
        );
    }
}
