<?php

namespace Phine\Phar\Builder\Subject;

use Exception;
use Phine\Observer\Subject;
use Phine\Phar\Builder\Arguments;
use Phine\Phar\Builder;
use Phine\Phar\Exception\BuilderException;

/**
 * Provides the basis for a builder event subject.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
abstract class AbstractSubject extends Subject
{
    /**
     * The managed method arguments to be used in an observer update.
     *
     * @var Arguments
     */
    protected $arguments;

    /**
     * The PHP archive builder.
     *
     * @var Builder
     */
    protected $builder;

    /**
     * The flag used to determine if an observer update is in process.
     *
     * @var boolean
     */
    private $updating = false;

    /**
     * Sets the PHP archive builder.
     *
     * @param Builder $builder The builder.
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Returns the method arguments to be used for an observer update.
     *
     * @return Arguments The managed method arguments.
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Checks if an observer update is in process.
     *
     * @return boolean Returns `true` if in process, `false` if not.
     */
    public function isUpdating()
    {
        return $this->updating;
    }

    /**
     * {@inheritDoc}
     */
    public function notifyObservers()
    {
        $this->updating = true;

        try {
            parent::notifyObservers();
        } catch (Exception $exception) {
            $this->updating = false;

            throw $exception;
        }

        $this->updating = false;

        $this->doLastStep();
    }

    /**
     * Sets the method arguments to be used for an observer update.
     *
     * @param Arguments $args The managed method arguments.
     *
     * @throws BuilderException If an observer update is in progress.
     */
    public function setArguments(Arguments $args)
    {
        if ($this->updating) {
            throw BuilderException::isUpdating();
        }

        $this->arguments = $args;
    }

    /**
     * Performs the last step after of the event.
     *
     * This method will only be called if all observers have been successfully
     * notified. If an error is triggered or the update process is interrupted,
     * this method will not be called.
     */
    abstract protected function doLastStep();
}
