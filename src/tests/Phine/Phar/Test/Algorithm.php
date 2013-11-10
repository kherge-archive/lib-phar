<?php

namespace Phine\Phar\Test;

use Phine\Phar\Signature\Algorithm\AbstractHashAlgorithm;

/**
 * A test algorithm using {@link AbstractHashAlgorithm}.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Algorithm extends AbstractHashAlgorithm
{
    /**
     * {@inheritDoc}
     */
    public function getFlag()
    {
        return 0x11;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAlgorithm()
    {
        return 'crc32';
    }

    /**
     * {@inheritDoc}
     */
    protected function getName()
    {
        return 'CRC32';
    }

    /**
     * {@inheritDoc}
     */
    protected function getSize()
    {
        return 4;
    }
}
