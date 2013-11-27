<?php

namespace Phine\Phar\Subject\Builder;

use Phine\Phar\Subject\AbstractSubject;

/**
 * Sets the stub for the archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SetStub extends AbstractSubject
{
    /**
     * @override
     */
    protected function doLastStep()
    {
        $this->builder->getPhar()->setStub($this->arguments['stub']);
    }
}
