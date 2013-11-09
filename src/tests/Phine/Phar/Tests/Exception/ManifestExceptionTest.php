<?php

namespace Phine\Phar\Tests\Exception;

use Phine\Phar\Exception\ManifestException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link ManifestException} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ManifestExceptionTest extends TestCase
{
    /**
     * Make sure that the method generates the expected message.
     */
    public function testInvalidMetadata()
    {
        $this->assertEquals(
            'The PHP archive file "/test/file" has invalid metadata.',
            ManifestException::invalidMetadata('/test/file')->getMessage(),
            'Make sure we get the expected message.'
        );
    }
}
