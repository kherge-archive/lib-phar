<?php

namespace Phine\Phar\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown for build errors.
 *
 * Summary
 * -------
 *
 * The `BuilderException` class is thrown for errors relating to the `Builder`
 * class and its processes. An example of this exception being thrown is when
 * the arguments of a builder subject are being changed while an update is in
 * progress.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuilderException extends Exception
{
    /**
     * Creates an exception for an undefined method argument.
     *
     * @param string $name The name of an argument.
     *
     * @return BuilderException The new exception.
     */
    public static function argNotDefined($name)
    {
        return new self(
            "The argument \"$name\" is not defined."
        );
    }

    /**
     * Creates an exception for changes being made during an update.
     *
     * @return BuilderException The new exception.
     */
    public static function isUpdating()
    {
        return new self(
            'The subject cannot be modified during an update.'
        );
    }
}
