<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Exception;

/**
 * Cannot delete group if marked as not deletable.
 *
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class CannotDeleteGroupException extends \RuntimeException implements ExceptionInterface
{
}
