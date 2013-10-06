<?php

namespace Phine\Phar;

use Phine\Exception\Exception;
use Phine\Phar\Exception\SignatureException;
use Phine\Phar\File\Reader;
use Phine\Phar\Signature\Algorithm;
use Phine\Phar\Signature\Algorithm\AlgorithmInterface;

/**
 * Reads and verifies an archive's signature without the `phar` extension.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Signature
{
    /**
     * The algorithm for the archive file.
     *
     * @var AlgorithmInterface
     */
    private $algorithm;

    /**
     * The collection of recognized algorithms.
     *
     * @var AlgorithmInterface[]
     */
    private $algorithms = array();

    /**
     * The archive file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * Sets the archive file reader.
     *
     * @param Reader $reader The archive file reader.
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Adds a signature algorithm to recognize.
     *
     * @param AlgorithmInterface $algorithm An algorithm.
     */
    public function addAlgorithm(AlgorithmInterface $algorithm)
    {
        $this->algorithms[spl_object_hash($algorithm)] = $algorithm;
    }

    /**
     * Creates a new instance with default algorithms registered.
     *
     * @param Reader|string $file The archive file path or reader.
     *
     * @return Signature The new instance.
     */
    public static function create($file)
    {
        if (is_string($file)) {
            $file = new Reader($file);
        }

        $signature = new self($file);
        $signature->addAlgorithm(new Algorithm\MD5());
        $signature->addAlgorithm(new Algorithm\SHA1());
        $signature->addAlgorithm(new Algorithm\SHA256());
        $signature->addAlgorithm(new Algorithm\SHA512());
        $signature->addAlgorithm(new Algorithm\OpenSSL());

        return $signature;
    }

    /**
     * Returns the algorithm used for the archive file.
     *
     * @return AlgorithmInterface The algorithm.
     *
     * @throws Exception
     * @throws SignatureException If the algorithm could not be found.
     */
    public function getAlgorithm()
    {
        if (null === $this->algorithm) {
            $this->reader->seek(-4, SEEK_END);

            if ('GBMB' !== $this->reader->read(4)) {
                throw SignatureException::createUsingFormat(
                    'The archive "%s" is not signed.',
                    $this->reader->getFile()
                );
            }

            $this->reader->seek(-8, SEEK_END);

            $flag = unpack('V', $this->reader->read(4));
            $flag = (int) $flag[1];

            foreach ($this->algorithms as $algorithm) {
                if ($flag === $algorithm->getFlag()) {
                    $this->algorithm = $algorithm;
                }
            }

            if (null === $this->algorithm) {
                throw SignatureException::createUsingFormat(
                    'The algorithm for the archive "%s" is not supported.',
                    $this->reader->getFile()
                );
            }
        }

        return $this->algorithm;
    }

    /**
     * Returns the signature stored in the archive file.
     *
     * @return array The signature.
     */
    public function getSignature()
    {
        return $this->getAlgorithm()->readSignature($this->reader);
    }

    /**
     * Verifies the signature of the archive file.
     *
     * @return boolean Returns `true` if verified, `false` if not.
     */
    public function verifySignature()
    {
        return $this->getAlgorithm()->verifySignature($this->reader);
    }
}
