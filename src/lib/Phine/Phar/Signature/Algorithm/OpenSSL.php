<?php

namespace Phine\Phar\Signature\Algorithm;

use Phine\Exception\Exception;
use Phine\Phar\Exception\FileException;
use Phine\Phar\Exception\SignatureException;
use Phine\Phar\File\Reader;

/**
 * Provides support for OpenSSL signed archives.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class OpenSSL implements AlgorithmInterface
{
    /**
     * Throws an exception if the `openssl` extension is not available.
     *
     * @throws SignatureException If the extension is not available.
     *
     * @api
     */
    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new SignatureException(
                'The "openssl" extension is not installed.'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x10;
    }

    /**
     * {@inheritDoc}
     */
    public function readSignature(Reader $reader)
    {
        $size = $this->getSize($reader);

        $reader->seek(-12 - $size, SEEK_END);

        $hash = unpack('H*', $reader->read($size));
        $hash = strtoupper($hash[1]);

        return array(
            'hash' => $hash,
            'hash_type' => 'OpenSSL'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function verifySignature(Reader $reader)
    {
        $expected = $this->readSignature($reader);
        $key = $this->readPublicKey($reader);
        $data = $this->getData($reader);

        $this->resetBuffer();

        ob_start();

        $result = openssl_verify($data, pack('H*', $expected['hash']), $key);
        $error = ob_get_clean();

        if (-1 === $result) {
            throw new SignatureException(openssl_error_string());
        } elseif (!empty($error)) {
            throw new SignatureException($error);
        }

        return (1 === $result);
    }

    /**
     * Reads the contents of the archive file without the signature.
     *
     * @param Reader $reader The archive file reader.
     *
     * @return string The archive data.
     */
    private function getData(Reader $reader)
    {
        $size = $this->getSize($reader);

        $reader->seek(0);

        return $reader->read($reader->getSize() - 12 - $size);
    }

    /**
     * Returns the size of the signature hash.
     *
     * @param Reader $reader The archive file reader.
     *
     * @return integer The size of the signature hash.
     */
    private function getSize(Reader $reader)
    {
        $reader->seek(-12, SEEK_END);

        $size = unpack('V', $reader->read(4));
        $size = (int) $size[1];

        return $size;
    }

    /**
     * Returns the public key for the archive file.
     *
     * @param Reader $reader The archive file reader.
     *
     * @return string The public key.
     *
     * @throws Exception
     * @throws FileException If the public key could not be read.
     */
    private function readPublicKey(Reader $reader)
    {
        $file = $reader->getFile() . '.pubkey';

        if (!file_exists($file)) {
            throw FileException::createUsingFormat(
                'The path "%s" is not a file or does not exist.',
                $file
            );
        }

        if (false === ($key = @file_get_contents($file))) {
            throw FileException::createUsingLastError();
        }

        return $key;
    }

    /**
     * Resets the OpenSSL error buffer.
     *
     * @param integer $max The maximum number of iterations.
     */
    private function resetBuffer($max = 100)
    {
        $count = 0;

        while (openssl_error_string()) {
            if (++$count === $max) {
                break;
            }
        }
    }
}
