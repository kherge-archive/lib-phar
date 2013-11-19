<?php

namespace Phine\Phar\Builder\Subject;

/**
 * Builds the archive using an iterator.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildIterator extends AbstractSubject
{
    /**
     * {@inheritDoc}
     */
    protected function doLastStep()
    {
        return $this->builder->getPhar()->buildFromIterator(
            $this->arguments['iterator'],
            $this->arguments['base']
        );
    }
}
