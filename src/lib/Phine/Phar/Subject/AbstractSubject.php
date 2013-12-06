<?php

namespace Phine\Phar\Subject;

use Exception;
use Phine\Observer\Subject;
use Phine\Phar\Builder;
use Phine\Phar\Exception\BuilderException;
use Phine\Phar\Subject\Arguments;

/**
 * Provides the basis for a builder event subject.
 *
 * Summary
 * -------
 *
 * The `AbstractSubject` class provides a foundation in which new subjects
 * can be easily created. The abstract class provides a way to accept `Builder`
 * and `Argument` instances, as well as making sure that the instances are not
 * accidentally replaced in mid update.
 *
 * Starting
 * --------
 *
 * To use the `AbstractSubject` class, you need to create your own.
 *
 *     class MySubject extends AbstractSubject
 *     {
 *         protected function doLastStep()
 *         {
 *             // do my thing here
 *         }
 *     }
 *
 * What ever action you need to do when the event concludes, you will need to
 * do it in the `doLastStep()` method. From this method, you will be able to
 * access the builder (`$this->builder`) and arguments (`$this->arguments`).
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
abstract class AbstractSubject extends Subject
{
    /**
     * The managed method arguments to be used in an observer update.
     *
     * @var Arguments
     *
     * @api
     */
    protected $arguments;

    /**
     * The PHP archive builder.
     *
     * @var Builder
     *
     * @api
     */
    protected $builder;

    /**
     * Sets the PHP archive builder.
     *
     * This method will create a new subject instances and set the builder.
     *
     *     use Phine\Phar\Subject\AbstractSubject;
     *
     *     $subject = new AbstractSubject($builder);
     *
     * > Note that you cannot actually instantiate the abstract subject class.
     * > You will need to create a class which would extend this one, and will
     * > implement the `doLastStep()` method.
     *
     * @param Builder $builder The builder.
     *
     * @api
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Returns the method arguments to be used for an observer update.
     *
     * This method will return the `Arguments` instance used for the subject.
     *
     *     $arguments = $subject->getArguments();
     *
     * @return Arguments The managed method arguments.
     *
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritDoc}
     */
    public function notifyObservers()
    {
        parent::notifyObservers();

        return $this->doLastStep();
    }

    /**
     * Sets the method arguments to be used for an observer update.
     *
     * This method will set the arguments passed to the method this subject
     * is for.
     *
     *     $subject->setArguments($arguments);
     *
     * Note that the arguments can only be changed before or after a subject
     * has been updated, and not during. Doing so will trigger an exception
     * since all the work any observer would have done will be lost.
     *
     * @param Arguments $args The managed method arguments.
     *
     * @throws BuilderException If an observer update is in progress.
     *
     * @api
     */
    public function setArguments(Arguments $args)
    {
        if ($this->isUpdating()) {
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
     *
     * @return mixed Any value, if available.
     *
     * @api
     */
    abstract protected function doLastStep();
}
