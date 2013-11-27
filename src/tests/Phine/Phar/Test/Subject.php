<?php

namespace Phine\Phar\Test;

use Phine\Phar\Subject\AbstractSubject;

/**
 * A test builder event subject.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Subject extends AbstractSubject
{
    /**
     * The flag used to indicate that the last step was run.
     *
     * @var boolean
     */
    public $done = false;

    /**
     * @override
     */
    protected function doLastStep()
    {
        $this->done = true;
    }
}
