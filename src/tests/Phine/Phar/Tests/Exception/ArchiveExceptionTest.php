<?php

namespace Phine\Phar\Tests\Exception;

use Phine\Phar\Exception\ArchiveException;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link ArchiveException} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArchiveExceptionTest extends TestCase
{
    /**
     * Make sure that the method generates the expected message.
     */
    public function testInvalidMetadata()
    {
        $this->assertEquals(
            'The data offset could not be found in the PHP archive file "/test/file".',
            ArchiveException::offsetNotFound('/test/file')->getMessage(),
            'Make sure we get the expected message.'
        );
    }
}
