<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Builds the archive using a directory path.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildDirectory extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        return $this->builder->getPhar()->buildFromDirectory(
            $this->arguments['dir'],
            $this->arguments['regex']
        );
    }
}
