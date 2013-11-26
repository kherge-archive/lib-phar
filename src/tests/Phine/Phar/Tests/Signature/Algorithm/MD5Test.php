<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\Signature\Algorithm\MD5;
use Phine\Test\Method;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link MD5} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class MD5Test extends TestCase
{
    /**
     * The algorithm instance being tested.
     *
     * @var MD5
     */
    private $algorithm;

    /**
     * Make sure that we get the expected flag.
     */
    public function testGetFlag()
    {
        $this->assertSame(
            0x01,
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
            'md5',
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
            'MD5',
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
            16,
            Method::invoke($this->algorithm, 'getSize'),
            'Make sure we get the right size.'
        );
    }

    /**
     * Creates a new instance of {@link MD5} for testing.
     */
    protected function setUp()
    {
        $this->algorithm = new MD5();
    }
}
