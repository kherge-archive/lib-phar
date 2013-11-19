<?php

namespace Phine\Phar\Builder\Subject;

/**
 * Adds a file from a string to the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddString extends AbstractSubject
{
    /**
     * {@inheritDoc}
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->addFromString(
            $this->arguments['local'],
            $this->arguments['contents']
        );
    }
}
