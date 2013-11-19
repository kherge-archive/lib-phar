<?php

namespace Phine\Phar\Builder\Subject;

/**
 * Adds a empty directory to the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddDirectory extends AbstractSubject
{
    /**
     * {@inheritDoc}
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->addEmptyDir($this->arguments['name']);
    }
}
