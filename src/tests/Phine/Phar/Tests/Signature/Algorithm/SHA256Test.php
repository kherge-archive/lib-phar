<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\Signature\Algorithm\SHA256;
use Phine\Test\Method;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link SHA256} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SHA256Test extends TestCase
{
    /**
     * The algorithm instance being tested.
     *
     * @var SHA256
     */
    private $algorithm;

    /**
     * Make sure that we get the expected flag.
     */
    public function testGetFlag()
    {
        $this->assertSame(
            0x03,
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
            'sha256',
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
            'SHA-256',
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
            32,
            Method::invoke($this->algorithm, 'getSize'),
            'Make sure we get the right size.'
        );
    }

    /**
     * Creates a new instance of {@link SHA256} for testing.
     */
    protected function setUp()
    {
        $this->algorithm = new SHA256();
    }
}
