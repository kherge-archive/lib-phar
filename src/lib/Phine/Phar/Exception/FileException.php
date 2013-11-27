<?php

namespace Phine\Phar\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown for file errors.
 *
 * Summary
 * -------
 *
 * The `FileException` class is thrown when file reader errors occur. This
 * class is mostly used in the `Reader` and `Writer` classes to indicate
 * that there may be a problem using the file system.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FileException extends Exception
{
}
