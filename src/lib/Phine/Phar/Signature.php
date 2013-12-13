<?php

namespace Phine\Phar;

use Phine\Exception\Exception;
use Phine\Phar\Exception\SignatureException;
use Phine\Phar\File\Reader;
use Phine\Phar\Signature\Algorithm;
use Phine\Phar\Signature\Algorithm\AlgorithmInterface;

/**
 * Reads and verifies an archive's signature.
 *
 * Summary
 * -------
 *
 * The `Signature` class uses algorithm classes to read and verify signatures
 * of archive files, without using the `phar` extension. The library includes
 * a class for each algorithm recognized by the `phar` extension: MD5, SHA1,
 * SHA256, SHA512, and OpenSSL. If you are verifying an archive signed using
 * a private key, you will need the `openssl` extension installed.
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Signature`.
 *
 *     use Phine\Phar\Signature;
 *
 *     $signature = Signature::create('example.phar');
 *
 * The above example will create a reader for the file, create a new instance
 * of the `Signature` class, and register all of the bundled algorithm classes
 * with the instance. If you know that you will only need one algorithm class
 * to verify a signature, you may manually create and register the instances
 * yourself:
 *
 *     use Phine\Phar\File\Reader;
 *     use Phine\Phar\Signature;
 *     use Phine\Phar\Signature\Algorithm\SHA1;
 *
 *     $reader = new Reader('example.phar');
 *
 *     $signature = new Signature($reader);
 *     $signature->addAlgorithm(new SHA1());
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
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
     * This method will create a new instance of the class using the given
     * file reader. On its own, the instance will not be able to extract or
     * verify any signature of any archive file. You will need to register
     * one or more algorithm classes with the new instance.
     *
     *     use Phine\Phar\File\Reader;
     *     use Phine\Phar\Signature;
     *
     *     $reader = new Reader('example.phar');
     *     $signature = new Signature($reader);
     *
     * @param Reader $reader The archive file reader.
     *
     * @api
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Adds a signature algorithm to recognize.
     *
     *     use Phine\Phar\Signature\Algorithm\SHA1;
     *
     *     $signature->addAlgorithm(new SHA1());
     *
     * @param AlgorithmInterface $algorithm An algorithm.
     *
     * @api
     */
    public function addAlgorithm(AlgorithmInterface $algorithm)
    {
        $this->algorithms[spl_object_hash($algorithm)] = $algorithm;
    }

    /**
     * Creates a new instance with default algorithms registered.
     *
     * This method will create a new file reader (or use a given one) for use
     * with a new `Signature` instance that will also be created. The bundled
     * algorithm classes will then be registered with the new instance so that
     * MD5, SHA1, and other signature types are recognized.
     *
     *     use Phine\Phar\Signature;
     *
     *     $signature = Signature::create('example.phar');
     *
     * @param Reader|string $file The archive file path or reader.
     *
     * @return Signature The new instance.
     *
     * @api
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

        if (extension_loaded('openssl')) {
            $signature->addAlgorithm(new Algorithm\OpenSSL());
        }

        return $signature;
    }

    /**
     * Returns the algorithm used for the archive file.
     *
     * This method will detect the algorithm used by the archive file and
     * return one of the registered algorithm classes that will be used to
     * extract and verify the signature.
     *
     *     // instance of Phine\Phar\Signature\Algorithm\SHA1
     *     $algorithm = $signature->getAlgorithm();
     *
     * > Note that only registered algorithms can be recognized by the
     * > instance. If an unrecognized algorithm is used by the archive
     * > file, an exception will be thrown.
     *
     * @return AlgorithmInterface The algorithm.
     *
     * @throws Exception
     * @throws SignatureException If the algorithm could not be found.
     *
     * @api
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
     * Returns the signature hash stored in the archive file.
     *
     * This method will return a hex encoded string that represents the
     * signature for the archive file. This hash can be used to compare
     * against the current hash of the archive file in order to verify
     * its integrity.
     *
     *     $hash = $signature->getHash();
     *
     * The `$hash` value returned is identical to that of the one returned
     * by the `Phar::getSignature()` instance method.
     *
     * @see Phar::getSignature()
     *
     * > Note that the actual hash for the archive is not as simple as
     * > calling `sha1_file()`. You will need to know where the archive
     * > data ends and the signature begins, and then create a hash using
     * > the archive data.
     *
     * @return array The signature.
     *
     * @api
     */
    public function getHash()
    {
        return $this->getAlgorithm()->readSignature($this->reader);
    }

    /**
     * Verifies the signature of the archive file.
     *
     * This method will check if the archive's signature is valid.
     *
     *     if ($signature->isValid()) {
     *         // verified
     *     }
     *
     * @return boolean Returns `true` if verified, `false` if not.
     *
     * @api
     */
    public function isValid()
    {
        return $this->getAlgorithm()->verifySignature($this->reader);
    }
}
