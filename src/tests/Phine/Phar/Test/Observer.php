<?php

namespace Phine\Phar\Test;

use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;

/**
 * An observer that interrupts.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Observer implements ObserverInterface
{
    /**
     * {@inheritDoc}
     */
    public function receiveUpdate(SubjectInterface $subject)
    {
        $subject->interruptUpdate();
    }
}
