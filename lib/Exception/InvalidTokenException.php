<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Exception;

/**
 * Token JSON invalid or expired
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
class InvalidTokenException extends \RuntimeException implements ExceptionInterface
{
}
