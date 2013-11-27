<?php

namespace Phine\Phar\Subject;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Phine\Phar\Exception\BuilderException;

/**
 * Manages a list of overridable method arguments.
 *
 * Summary
 * -------
 *
 * The `Arguments` class is used to manage arguments passed to a method, which
 * is then passed on to an event observer through the subject it is observing.
 * An instance of `Arguments` will contain a finite set of arguments that cannot
 * be added or removed from, however, the values can be changed. If a value is
 * changed, an observer will still be able to retrieve the original argument
 * value that was passed to the method.
 *
 * > Note that values as changed will be preserved for the next observer to
 * > use.
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Arguments`.
 *
 *     use Phine\Phar\Subject\Arguments;
 *
 *     $arguments = new Arguments(
 *         array(
 *             'name' => 'value'
 *         )
 *     );
 *
 * The array key is the name of an argument, and the value is the value of the
 * argument. Once the instance has been created, new arguments cannot be set,
 * and existing arguments cannot be removed.
 *
 * ### Changing Values
 *
 * To change the value of an argument, access the instance as an array and set
 * the value.
 *
 *     $arguments['name'] = 'new value';
 *
 * To access the original value, you will need to call the `getOriginalValue()`
 * method.
 *
 *     $originalValue = $arguments->getOriginalValue('name'); // "value"
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
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
     * This method will set the list of original argument values.
     *
     *     use Phine\Phar\Subject\Arguments;
     *
     *     $arguments = new Arguments(
     *         array(
     *             'name' => 'value'
     *         )
     *     );
     *
     * @param array $args The method arguments.
     *
     * @api
     */
    public function __construct(array $args)
    {
        $this->original = $args;
    }

    /**
     * Returns the current method arguments for iterating.
     *
     * This method will return an array iterator. It will... allow you to
     * iterate through the arguments.
     *
     *     foreach ($arguments as $name => $value) {
     *         // iteratin'
     *     }
     *
     * @return ArrayIterator The arguments iterator.
     *
     * @api
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
     * This method will return the original value of the specified argument.
     *
     *     $originalValue = $arguments->getOriginalValue('name');
     *
     * @param string $name The name of an argument.
     *
     * @throws BuilderException If the argument is not defined.
     *
     * @api
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
     * This method will check if the specified argument exists.
     *
     *     if (isset($arguments['name'])) {
     *         // exists
     *     } else {
     *         // does not exist
     *     }
     *
     * Note that even if an argument value is `null`, `true` will still be
     * returned if the argument has been defined.
     *
     * @param string $name The name of an argument.
     *
     * @return boolean Returns `true` if defined, `false` if not.
     *
     * @api
     */
    public function offsetExists($name)
    {
        return array_key_exists($name, $this->original);
    }

    /**
     * Returns the value for an argument.
     *
     * This method will return the value for the argument.
     *
     *     $value = $arguments['name'];
     *
     * If the original argument value was changed, the new value will be
     * returned. If the original argument value was not changed, the original
     * value will be returned.
     *
     * @param string $name The name of an argument.
     *
     * @return mixed The value of the argument.
     *
     * @throws BuilderException If the argument is not defined.
     *
     * @api
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
     * This method will replace the original value of the argument.
     *
     *     $arguments['name'] = 'new value';
     *
     * Technically, the value is not replace. What will happen is that the
     * new value set will be returned (when accessed) instead of the original
     * one.
     *
     * @param string $name  The name of an argument.
     * @param mixed  $value The value of the argument.
     *
     * @throws BuilderException If the argument is not defined.
     *
     * @api
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
     * This method will restore the original value of an argument.
     *
     *     $arguments['name'] = 'new value';
     *
     *     unset($arguments['name']);
     *
     *     echo $arguments['name']; // "value"
     *
     * If the original value is already restored, `unset()`ing an argument
     * will not do anything.
     *
     * @param string $name The name of an argument.
     *
     * @throws BuilderException If the argument is not defined.
     *
     * @api
     */
    public function offsetUnset($name)
    {
        if (!array_key_exists($name, $this->original)) {
            throw BuilderException::argNotDefined($name);
        }

        unset($this->override[$name]);
    }
}
