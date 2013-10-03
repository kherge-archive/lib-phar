<?php

namespace Phine\Phar\Builder\Subject;

/**
 * Builds the archive using a directory path.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildDirectory extends AbstractSubject
{
    /**
     * {@inheritDoc}
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->buildFromDirectory(
            $this->arguments['dir'],
            $this->arguments['regex']
        );
    }
}
