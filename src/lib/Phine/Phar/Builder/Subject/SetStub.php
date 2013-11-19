<?php

namespace Phine\Phar\Builder\Subject;

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
