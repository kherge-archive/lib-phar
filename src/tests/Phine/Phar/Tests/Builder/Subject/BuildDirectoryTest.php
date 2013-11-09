<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link BuildDirectoryTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildDirectoryTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::BUILD_DIR;

    /**
     * Make sure that we can build from a directory.
     */
    public function testDoLastStep()
    {
        $this->invokeSubject(
            array(
                'dir' => __DIR__,
                'regex' => '/Add/'
            )
        );

        $args = $this->subject->getArguments();

        $this->assertEquals(
            __DIR__,
            $args['dir'],
            'Make sure the directory path is provided.'
        );

        $this->assertEquals(
            '/Add/',
            $args['regex'],
            'Make sure the regular expression is provided.'
        );
    }
}
