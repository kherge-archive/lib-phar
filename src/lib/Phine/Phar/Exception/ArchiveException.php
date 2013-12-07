<?php

namespace Phine\Phar\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown for archive related errors.
 *
 * Summary
 * -------
 *
 * The `ArchiveException` class is thrown for errors inside the `Archive`
 * class. An example of this exception being used is when the data offset
 * for the archive is not valid (or could not be found).
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ArchiveException extends Exception
{
    /**
     * Creates a new exception if an offset is not found.
     *
     * @param string $file The archive file path.
     *
     * @return ArchiveException The new exception.
     */
    public static function offsetNotFound($file)
    {
        return new self(
            "The data offset could not be found in the PHP archive file \"$file\"."
        );
    }
}
