<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Invalid response from MailUP
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
class InvalidResponseException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response, $message = "", $code = 0, \Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the response that generated the exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
