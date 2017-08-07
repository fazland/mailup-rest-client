<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

use Psr\Http\Message\ResponseInterface;

/**
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
abstract class Resource
{
    /**
     * Converts a JSON response body into an array
     *
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    public static function getJSON(ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        return json_decode($body, true);
    }
}
