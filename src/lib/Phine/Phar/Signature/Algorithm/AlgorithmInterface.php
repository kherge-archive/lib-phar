<?php

namespace Phine\Phar\Signature\Algorithm;

use Phine\Phar\File\Reader;

/**
 * Defines how a signature algorithm class must be implemented.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
interface AlgorithmInterface
{
    /**
     * Returns the signature algorithm flag.
     *
     * @return integer The signature algorithm flag.
     */
    public function getFlag();

    /**
     * Reads the signature from the archive file.
     *
     * @param Reader $reader The archive file reader.
     */
    public function readSignature(Reader $reader);

    /**
     * Verifies the signature for the archive file.
     *
     * @param Reader $reader The archive file reader.
     *
     * @return boolean Returns `true` if verified, `false` if not.
     */
    public function verifySignature(Reader $reader);
}
