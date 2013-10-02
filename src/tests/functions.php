<?php

/**
 * Calls a protected or private method.
 *
 * @param object|string $class The class name or object.
 * @param string $method The name of the method.
 * @param array $arguments The list of arguments.
 *
 * @return mixed The result of method call.
 *
 * @throws RuntimeException If the method does not exist.
 */
function call($class, $method, array $arguments = array())
{
    $reflection = new ReflectionClass($class);

    while (!$reflection->hasMethod($method)) {
        if (!($reflection = $reflection->getParentClass())) {
            throw new RuntimeException(
                sprintf(
                    'The class %s does not have the method %s.',
                    is_string($class) ? $class : get_class($class),
                    $method
                )
            );
        }
    }

    $reflection = $reflection->getMethod($method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs(
        is_object($class) ? $class : null,
        $arguments
    );
}

/**
 * Returns the value of a (even static) protected or private property.
 *
 * @param object|string $class The class name or object.
 * @param string $property The name of the property.
 *
 * @return mixed The value of the property.
 */
function get($class, $property)
{
    return property($class, $property)->getValue(
        is_object($class) ? $class : null
    );
}

/**
 * Returns a (even static) protected or private property.
 *
 * @param object|string $class The class name or object.
 * @param string $property The name of the property.
 *
 * @return ReflectionProperty The property.
 *
 * @throws RuntimeException If the property does not exist.
 */
function property($class, $property)
{
    $reflection = new ReflectionClass($class);

    while (!$reflection->hasProperty($property)) {
        if (!($reflection = $reflection->getParentClass())) {
            throw new RuntimeException(
                sprintf(
                    'The class %s does not have the property %s.',
                    is_string($class) ? $class : get_class($class),
                    $property
                )
            );
        }
    }

    $reflection = $reflection->getProperty($property);
    $reflection->setAccessible(true);

    return $reflection;
}

/**
 * Sets the value of a (even static) protected or private property.
 *
 * @param object|string $class The class name or object.
 * @param string $property The name of the property.
 * @param mixed $value The new value of the property.
 */
function set($class, $property, $value)
{
    property($class, $property)->setValue(
        is_object($class) ? $class : null,
        $value
    );
}
