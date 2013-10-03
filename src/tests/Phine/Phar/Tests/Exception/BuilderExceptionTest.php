<?php

namespace Phine\Phar\Tests\Exception;

use Phine\Phar\Exception\BuilderException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link BuilderExceptionTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuilderExceptionTest extends TestCase
{
    /**
     * Make sure that the method generates the expected message.
     */
    public function testArgNotDefined()
    {
        $this->assertEquals(
            'The argument "test" is not defined.',
            BuilderException::argNotDefined('test')->getMessage(),
            'Make sure we get the expected message.'
        );
    }
}
