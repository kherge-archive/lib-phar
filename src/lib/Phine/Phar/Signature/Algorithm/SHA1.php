<?php

namespace Phine\Phar\Signature\Algorithm;

/**
 * Provides support for the SHA1 algorithm.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class SHA1 extends AbstractHashAlgorithm
{
    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x02;
    }

    /**
     * @override
     */
    protected function getAlgorithm()
    {
        return 'sha1';
    }

    /**
     * @override
     */
    protected function getName()
    {
        return 'SHA-1';
    }

    /**
     * @override
     */
    protected function getSize()
    {
        return 20;
    }
}
