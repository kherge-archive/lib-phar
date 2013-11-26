<?php

namespace Phine\Phar\Signature\Algorithm;

/**
 * Provides support for the SHA512 algorithm.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class SHA512 extends AbstractHashAlgorithm
{
    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x04;
    }

    /**
     * @override
     */
    protected function getAlgorithm()
    {
        return 'sha512';
    }

    /**
     * @override
     */
    protected function getName()
    {
        return 'SHA-512';
    }

    /**
     * @override
     */
    protected function getSize()
    {
        return 64;
    }
}
