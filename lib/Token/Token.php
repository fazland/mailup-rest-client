<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Token;

use Fazland\MailUpRestClient\Exception\InvalidTokenException;
use Psr\Http\Message\ResponseInterface;

/**
 * Access/Refresh token pair for OAuth authentication
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
final class Token implements TokenInterface
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var int
     */
    private $validUntil;

    /**
     * @var string
     */
    private $refreshToken;

    public function __construct(string $accessToken, int $validUntilTimestamp, string $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->validUntil = $validUntilTimestamp;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Creates a token instance from JSON string
     *
     * @param string $json
     *
     * @return Token
     *
     * @throws InvalidTokenException If JSON is not valid or token is already expired
     */
    public static function fromJson(string $json): self
    {
        $object = @json_decode($json);
        if (null === $object || ! isset($object->accessToken, $object->validUntil, $object->refreshToken)) {
            throw new InvalidTokenException();
        }

        $token = new self($object->accessToken, $object->validUntil, $object->refreshToken);
        if (! $token->isValid()) {
            throw new InvalidTokenException();
        }

        return $token;
    }

    /**
     * Creates a new instance from the server response
     *
     * @param ResponseInterface $response
     *
     * @return Token
     *
     * @throws InvalidTokenException If JSON response is not valid
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $json = (string)$response->getBody();
        $object = @json_decode($json);
        if (null === $object || ! isset($object->access_token, $object->expires_in, $object->refresh_token)) {
            throw new InvalidTokenException();
        }

        $token = new self($object->access_token, time() + $object->expires_in, $object->refresh_token);
        return $token;
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return $this->validUntil < time() + 30;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeRefreshed(): bool
    {
        return $this->validUntil < time() + 180;
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'accessToken' => $this->accessToken,
            'validUntil' => $this->validUntil,
            'refreshToken' => $this->refreshToken,
        ];
    }
}
