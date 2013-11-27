<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Adds a empty directory to the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddDirectory extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->addEmptyDir($this->arguments['name']);
    }
}
