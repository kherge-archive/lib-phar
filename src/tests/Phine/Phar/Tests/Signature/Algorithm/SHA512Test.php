<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\Signature\Algorithm\SHA512;
use Phine\Test\Method;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link SHA512} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SHA512Test extends TestCase
{
    /**
     * The algorithm instance being tested.
     *
     * @var SHA512
     */
    private $algorithm;

    /**
     * Make sure that we get the expected flag.
     */
    public function testGetFlag()
    {
        $this->assertSame(
            0x04,
            $this->algorithm->getFlag(),
            'Make sure we get the right flag.'
        );
    }

    /**
     * Make sure that we get the expected algorithm name.
     */
    public function testGetAlgorithm()
    {
        $this->assertEquals(
            'sha512',
            Method::invoke($this->algorithm, 'getAlgorithm'),
            'Make sure we get the right algorithm.'
        );
    }

    /**
     * Make sure that we get the expected name as returned by `Phar::getHash()`.
     */
    public function testGetName()
    {
        $this->assertEquals(
            'SHA-512',
            Method::invoke($this->algorithm, 'getName'),
            'Make sure we get the right name.'
        );
    }

    /**
     * Make sure that we get the expected hash size.
     */
    public function testGetSize()
    {
        $this->assertSame(
            64,
            Method::invoke($this->algorithm, 'getSize'),
            'Make sure we get the right size.'
        );
    }

    /**
     * Creates a new instance of {@link SHA512} for testing.
     */
    protected function setUp()
    {
        $this->algorithm = new SHA512();
    }
}
