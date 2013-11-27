<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Adds a file from a string to the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddString extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->addFromString(
            $this->arguments['local'],
            $this->arguments['contents']
        );
    }
}
