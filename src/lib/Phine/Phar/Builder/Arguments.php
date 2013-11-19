<?php

namespace Phine\Phar\Builder;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Phine\Phar\Exception\BuilderException;

/**
 * Manages a list of overridable method arguments.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Arguments implements ArrayAccess, IteratorAggregate
{
    /**
     * The original method arguments.
     *
     * @var array
     */
    private $original = array();

    /**
     * The overriding method arguments.
     *
     * @var array
     */
    private $override = array();

    /**
     * Sets the original method arguments.
     *
     * @param array $args The method arguments.
     */
    public function __construct(array $args)
    {
        $this->original = $args;
    }

    /**
     * Returns the current method arguments for iterating.
     *
     * @return ArrayIterator The arguments iterator.
     */
    public function getIterator()
    {
        return new ArrayIterator(
            array_replace($this->original, $this->override)
        );
    }

    /**
     * Returns the original value for an argument.
     *
     * @param string $name The name of an argument.
     *
     * @throws BuilderException If the argument is not defined.
     */
    public function getOriginalValue($name)
    {
        if (!isset($this->original[$name])) {
            throw BuilderException::argNotDefined($name);
        }

        return $this->original[$name];
    }

    /**
     * Checks if an argument is defined.
     *
     * @param string $name The name of an argument.
     *
     * @return boolean Returns `true` if defined, `false` if not.
     */
    public function offsetExists($name)
    {
        return array_key_exists($name, $this->original);
    }

    /**
     * Returns the value for an argument.
     *
     * @param string $name The name of an argument.
     *
     * @return mixed The value of the argument.
     *
     * @throws BuilderException If the argument is not defined.
     */
    public function offsetGet($name)
    {
        if (!array_key_exists($name, $this->original)) {
            throw BuilderException::argNotDefined($name);
        }

        if (array_key_exists($name, $this->override)) {
            return $this->override[$name];
        }

        return $this->original[$name];
    }

    /**
     * Sets the override value for an argument.
     *
     * @param string $name  The name of an argument.
     * @param mixed  $value The value of the argument.
     *
     * @throws BuilderException If the argument is not defined.
     */
    public function offsetSet($name, $value)
    {
        if (!array_key_exists($name, $this->original)) {
            throw BuilderException::argNotDefined($name);
        }

        $this->override[$name] = $value;
    }

    /**
     * Restores the original value of an argument.
     *
     * @param string $name The name of an argument.
     *
     * @throws BuilderException If the argument is not defined.
     */
    public function offsetUnset($name)
    {
        if (!array_key_exists($name, $this->original)) {
            throw BuilderException::argNotDefined($name);
        }

        unset($this->override[$name]);
    }
}
