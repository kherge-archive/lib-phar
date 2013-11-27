<?php

namespace Phine\Phar\Tests\Subject\Builder;

use Phine\Phar\Builder;
use Phine\Phar\Tests\Subject\AbstractTestCase;

/**
 * Tests the methods in the {@link SetStub} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SetStubTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::SET_STUB;

    /**
     * Make sure that we can add a file.
     */
    public function testDoLastStep()
    {
        $stub = '<?php __HALT_COMPILER();';

        $this
            ->phar
            ->expects($this->once())
            ->method('setStub')
            ->with(
                $this->equalTo($stub)
            );

        $this->invokeSubject(
            array(
                'stub' => $stub
            )
        );
    }
}
