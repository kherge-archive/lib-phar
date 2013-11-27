<?php

namespace Phine\Phar\Tests\Subject\Builder;

use Phine\Phar\Builder;
use Phine\Phar\Tests\Subject\AbstractTestCase;

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
