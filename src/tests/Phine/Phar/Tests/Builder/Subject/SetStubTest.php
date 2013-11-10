<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;

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

        $this->invokeSubject(
            array(
                'stub' => $stub
            )
        );

        $args = $this->subject->getArguments();

        $this->assertEquals(
            $stub,
            $args['stub'],
            'Make sure the stub is provided.'
        );
    }
}
