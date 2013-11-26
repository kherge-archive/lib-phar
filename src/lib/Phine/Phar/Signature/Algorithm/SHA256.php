<?php

namespace Phine\Phar\Signature\Algorithm;

/**
 * Provides support for the SHA256 algorithm.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class SHA256 extends AbstractHashAlgorithm
{
    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x03;
    }

    /**
     * @override
     */
    protected function getAlgorithm()
    {
        return 'sha256';
    }

    /**
     * @override
     */
    protected function getName()
    {
        return 'SHA-256';
    }

    /**
     * @override
     */
    protected function getSize()
    {
        return 32;
    }
}
