<?php

namespace Phine\Phar\Exception;

use Phine\Exception\Exception;

/**
 * Exception thrown for signature errors.
 *
 * Summary
 * -------
 *
 * The `SignatureException` class is thrown for errors inside the `Signature`
 * class, or with one of its algorithm classes. An example of this exception
 * being thrown is if an attempt is made to verify the signature of an archive
 * that does not have a signature.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class SignatureException extends Exception
{
}
