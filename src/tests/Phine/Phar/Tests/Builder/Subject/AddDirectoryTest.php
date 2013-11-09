<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link AddDirectoryTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddDirectoryTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::ADD_DIR;

    /**
     * Make sure that we can add an empty directory.
     */
    public function testDoLastStep()
    {
        $this->invokeSubject(
            array(
                'name' => 'test'
            )
        );

        $this->assertEquals(
            'test',
            $this->subject->getArguments()->offsetGet('name'),
            'Make sure the directory name is provided.'
        );
    }
}
