<?php

namespace Phine\Phar\Tests;

use Phine\Phar\File\Reader;
use Phine\Phar\Signature;
use Phine\Phar\Signature\Algorithm\AlgorithmInterface;
use Phine\Phar\Signature\Algorithm\SHA1;
use Phine\Phar\Test\Algorithm;
use Phine\Test\Property;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link Signature} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SignatureTest extends TestCase
{
    /**
     * The test file being read.
     *
     * @var string
     */
    private $file;

    /**
     * The actual signature hash.
     *
     * @var string
     */
    private $hash;

    /**
     * The signature instance being tested.
     *
     * @var Signature
     */
    private $signature;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure we can add an algorithm to use.
     */
    public function testAddAlgorithm()
    {
        $algorithm = new SHA1();

        $this->signature->addAlgorithm($algorithm);

        $algorithms = Property::get($this->signature, 'algorithms');

        $this->assertSame(
            $algorithm,
            $algorithms[spl_object_hash($algorithm)],
            'Make sure the algorithm is added.'
        );
    }

    /**
     * Make sure that we can create an instance of Signature. We should be
     * able to use a string file path or instance of Reader, and all included
     * algorithms should be registered.
     */
    public function testCreate()
    {
        $signature = Signature::create($this->file);

        /** @var AlgorithmInterface[] $algorithms */
        $algorithms = Property::get($signature, 'algorithms');

        /** @var Reader $reader */
        $reader = Property::get($signature, 'reader');

        $this->assertEquals(
            $this->file,
            $reader->getFile(),
            'Make sure we get back the file path we put in.'
        );

        $expected = array(
            'Phine\\Phar\\Signature\\Algorithm\\MD5',
            'Phine\\Phar\\Signature\\Algorithm\\SHA1',
            'Phine\\Phar\\Signature\\Algorithm\\SHA256',
            'Phine\\Phar\\Signature\\Algorithm\\SHA512',
        );

        if (extension_loaded('openssl')) {
            $expected[] = 'Phine\\Phar\\Signature\\Algorithm\\OpenSSL';
        }

        foreach ($expected as $algorithm) {
            $this->assertInstanceOf(
                $algorithm,
                array_shift($algorithms),
                'Make sure the algorithm is registered.'
            );
        }
    }

    /**
     * Make sure that we can the algorithm used for the signature.
     */
    public function testGetAlgorithm()
    {
        $this->assertInstanceOf(
            'Phine\\Phar\\Test\\Algorithm',
            $this->signature->getAlgorithm(),
            'Make sure the CRC32 algorithm is returned.'
        );
    }

    /**
     * Make sure an exception is thrown if the file is not signed.
     */
    public function testGetAlgorithmNotSigned()
    {
        file_put_contents($this->file, 'This file is not signed.');

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\SignatureException',
            'The archive "' . $this->file . '" is not signed.'
        );

        $this->signature->getAlgorithm();
    }

    /**
     * Make sure an exception is thrown if the algorithm is not recognized.
     */
    public function testGetAlgorithmNotRecognized()
    {
        Property::set($this->signature, 'algorithms', array());

        $this->setExpectedException(
            'Phine\\Phar\\Exception\\SignatureException',
            'The algorithm for the archive "' . $this->file . '" is not supported.'
        );

        $this->signature->getAlgorithm();
    }

    /**
     * Make sure that we can read the signature from the archive.
     */
    public function testGetHash()
    {
        $this->assertSame(
            array(
                'hash' => $this->hash,
                'hash_type' => 'CRC32'
            ),
            $this->signature->getHash(),
            'Make sure we can read the signature.'
        );
    }

    /**
     * Make sure that we can verify the signature of the archive.
     */
    public function testIsValid()
    {
        $this->assertTrue(
            $this->signature->isValid(),
            'Make sure the archive is verified.'
        );
    }

    /**
     * Creates a test file for reading and verifying.
     */
    protected function setUp()
    {
        $this->temp = new Temp();
        $this->file = $this->temp->createFile();
        $this->hash = strtoupper(hash('crc32', 'This is the test content.'));

        file_put_contents(
            $this->file,
            sprintf(
                '%s%s%s%s',
                'This is the test content.',
                pack('H*', $this->hash),
                pack('V', 0x11),
                'GBMB'
            )
        );

        $this->signature = new Signature(new Reader($this->file));

        Property::set($this->signature, 'algorithms', array(new Algorithm()));
    }

    /**
     * Creates the test file.
     */
    protected function tearDown()
    {
        $this->temp->purgePaths();
    }
}
