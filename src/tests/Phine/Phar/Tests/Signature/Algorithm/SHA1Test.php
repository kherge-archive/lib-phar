<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\Signature\Algorithm\SHA1;
use Phine\Test\Method;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link SHA1} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SHA1Test extends TestCase
{
    /**
     * The algorithm instance being tested.
     *
     * @var SHA1
     */
    private $algorithm;

    /**
     * Make sure that we get the expected flag.
     */
    public function testGetFlag()
    {
        $this->assertSame(
            0x02,
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
            'sha1',
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
            'SHA-1',
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
            20,
            Method::invoke($this->algorithm, 'getSize'),
            'Make sure we get the right size.'
        );
    }

    /**
     * Creates a new instance of {@link SHA1} for testing.
     */
    protected function setUp()
    {
        $this->algorithm = new SHA1();
    }
}
