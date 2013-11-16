<?php

namespace Phine\Phar\Tests\Signature\Algorithm;

use Phine\Phar\File\Reader;
use Phine\Phar\Signature\Algorithm\AbstractHashAlgorithm;
use Phine\Phar\Test\Algorithm;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests the methods in the {@link ${CLASS}} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AbstractHashAlgorithmTest extends TestCase
{
    /**
     * The test algorithm.
     *
     * @var AbstractHashAlgorithm
     */
    private $algorithm;

    /**
     * The test file.
     *
     * @var string
     */
    private $file;

    /**
     * The actual hash for the file.
     *
     * @var string
     */
    private $hash;

    /**
     * The reader for the test file.
     *
     * @var Reader
     */
    private $reader;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure we can read the hash from the archive file.
     */
    public function testReadSignature()
    {
        $signature = $this->algorithm->readSignature($this->reader);

        $this->assertSame(
            array(
                'hash' => $this->hash,
                'hash_type' => 'CRC32'
            ),
            $signature,
            'Make sure we get the expected signature.'
        );
    }

    /**
     * Creates a new instance of the test algorithm.
     */
    protected function setUp()
    {
        $this->algorithm = new Algorithm();
        $this->temp = new Temp();
        $this->file = $this->temp->createFile();
        $this->hash = strtoupper(hash('crc32', 'This is the test contents.'));
        $this->reader = new Reader($this->file);

        file_put_contents(
            $this->file,
            sprintf(
                '%s%s%s%s',
                'This is the test contents.',
                pack('H*', $this->hash),
                pack('V', $this->algorithm->getFlag()),
                'GBMB'
            )
        );
    }

    /**
     * Clean up the test file.
     */
    protected function tearDown()
    {
        $this->temp->purgePaths();
    }
}
