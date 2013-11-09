<?php

namespace Phine\Phar\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown for manifest related errors.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ManifestException extends Exception
{
    /**
     * Creates a new exception if an offset is not found.
     *
     * @param string $file The archive file path.
     *
     * @return ManifestException The new exception.
     */
    public static function offsetNotFound($file)
    {
        return new self(
            "The manifest offset could not be found in the PHP archive file \"$file\"."
        );
    }

    /**
     * Creates a new exception for a manifest with invalid metadata.
     *
     * @param string $file The archive file path.
     *
     * @return ManifestException The new exception.
     */
    public static function invalidMetadata($file)
    {
        return new self(
            "The PHP archive file \"$file\" has invalid metadata."
        );
    }
}
