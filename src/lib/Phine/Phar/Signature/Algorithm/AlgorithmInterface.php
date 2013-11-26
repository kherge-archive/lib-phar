<?php

namespace Phine\Phar\Signature\Algorithm;

use Phine\Phar\File\Reader;

/**
 * Defines how a signature algorithm class must be implemented.
 *
 * @link http://us1.php.net/manual/en/phar.fileformat.signature.php Signature Format
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
interface AlgorithmInterface
{
    /**
     * Returns the signature algorithm flag.
     *
     * This method will return the flag used to identify the algorithm used
     * to sign an archive file.
     *
     *     $flag = $algorithm->getFlag();
     *
     *     if (0x0002 === $flag) {
     *         // SHA1
     *     }
     *
     * @return integer The signature algorithm flag.
     *
     * @api
     */
    public function getFlag();

    /**
     * Reads the signature from the archive file.
     *
     * This method will read the signature type and hash from the archive file
     * using the given reader. The value returned is identical to that returned
     * by the `Phar::getSignature()` instance method.
     *
     *     $signature = $algorithm->readSignature($reader);
     *
     * The returned value will look something like this:
     *
     *     $signature = array(
     *         'hash' => 'F6C4F20BEB430D16F79E295766848D0C001D1E40',
     *         'hash_type' => 'SHA1',
     *     );
     *
     * @param Reader $reader The archive file reader.
     *
     * @return array The read signature type and hash.
     *
     * @api
     */
    public function readSignature(Reader $reader);

    /**
     * Verifies the signature for the archive file.
     *
     * This method will verify the actual signature of the archive against
     * the embedded signature. If the actual signature does not match the
     * expected (embedded) one, `false` will be returned.
     *
     *     if ($algorithm->verifySignature($reader)) {
     *         // verified
     *     }
     *
     * @param Reader $reader The archive file reader.
     *
     * @return boolean Returns `true` if verified, `false` if not.
     *
     * @api
     */
    public function verifySignature(Reader $reader);
}
