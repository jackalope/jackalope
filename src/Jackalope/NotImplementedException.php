<?php
namespace Jackalope;

use RuntimeException;

/**
 * Exception to throw when something has not yet been implemented.
 *
 * For optional features, use
 * \PHPCR\UnsupportedRepositoryOperationException rather than this exception.
 *
 * Should become obsolete once everything is implemented.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NotImplementedException extends RuntimeException
{
}
