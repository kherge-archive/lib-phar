<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Builds the archive using an iterator.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildIterator extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        return $this->builder->getPhar()->buildFromIterator(
            $this->arguments['iterator'],
            $this->arguments['base']
        );
    }
}
