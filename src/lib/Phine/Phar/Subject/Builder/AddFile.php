<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Adds a file from the disk to the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddFile extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        if ($this->arguments['local']) {
            $this->builder->getPhar()->addFile(
                $this->arguments['file'],
                $this->arguments['local']
            );
        } else {
            $this->builder->getPhar()->addFile($this->arguments['file']);
        }
    }
}
