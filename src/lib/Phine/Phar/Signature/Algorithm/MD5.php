<?php

namespace Phine\Phar\Signature\Algorithm;

/**
 * Provides support for the MD5 algorithm.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class MD5 extends AbstractHashAlgorithm
{
    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x01;
    }

    /**
     * @override
     */
    protected function getAlgorithm()
    {
        return 'md5';
    }

    /**
     * @override
     */
    protected function getName()
    {
        return 'MD5';
    }

    /**
     * @override
     */
    protected function getSize()
    {
        return 16;
    }
}
