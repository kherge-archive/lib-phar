<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link AddDirectory} class.
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
        $this
            ->phar
            ->expects($this->once())
            ->method('addEmptyDir')
            ->with(
                $this->equalTo('test')
            );

        $this->invokeSubject(
            array(
                'name' => 'test'
            )
        );
    }
}
