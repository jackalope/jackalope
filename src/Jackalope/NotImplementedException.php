<?php
namespace Jackalope;

/**
 * Exception to throw when something has not yet been implemented.
 *
 * For optional features, use
 * \PHPCR\UnsupportedRepositoryOperationException rather than this exception.
 *
 * Should become obsolete once everything is implemented.
 */
class NotImplementedException extends \RuntimeException
{
}
