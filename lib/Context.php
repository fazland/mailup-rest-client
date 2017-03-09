<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

use Fazland\MailUpRestClient\Exception\InvalidResponseException;
use Fazland\MailUpRestClient\Exception\InvalidTokenException;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MailUP request context (authentication, endpoints, etc)
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
class Context
{
    const AUTH_TOKEN_URI = 'https://services.mailup.com/Authorization/OAuth/Token';
    const BASE_URI = 'https://services.mailup.com/API/v1.1/Rest';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Token\TokenInterface
     */
    private $token;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    public function __construct(array $options = [], HttpClient $client = null)
    {
        if (null === $client) {
            $client = HttpClientDiscovery::find();
        }

        $this->token = new Token\NullToken();
        $this->client = $client;
        $this->messageFactory = MessageFactoryDiscovery::find();

        $options = $this->resolveOptions($options);

        $this->setCacheDir($options['cache_dir']);
        $this->clientId = $options['client_id'];
        $this->clientSecret = $options['client_secret'];
        $this->username = $options['username'];
        $this->password = $options['password'];
    }

    /**
     * Sets the cache dir for the MailUP context.
     * Setting it to null will disable caching.
     *
     * @param string|null $cacheDir
     *
     * @internal Access token JSON file will be saved here.
     */
    public function setCacheDir(string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir;

        if (null !== $this->cacheDir && file_exists($fn = $this->cacheDir.DIRECTORY_SEPARATOR.'access_token.json')) {
            if (false === $json = @file_get_contents($fn)) {
                // Error
                return;
            }

            try {
                $this->token = Token\Token::fromJson($json);
            } catch (InvalidTokenException $ex) {
                return;
            }
        }
    }

    /**
     * Performs a request to MailUP API and returns a response object.
     * Please DO NOT use this method directly.
     *
     * @internal
     *
     * @param string $path
     * @param string $method
     * @param array|null $params
     *
     * @return ResponseInterface
     *
     * @throws InvalidResponseException
     */
    public function makeRequest(string $path, string $method, $params = null): ResponseInterface
    {
        $this->refreshToken();

        $request = $this->messageFactory->createRequest($method, self::BASE_URI.$path, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->token->getAccessToken(),
        ], json_encode($params));

        $response = $this->client->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            $responseBody = (string)$response->getBody();
            $message = "Response not OK when requesting an access token. Response body: $responseBody";

            throw new InvalidResponseException($response, $message);
        }

        return $response;
    }

    /**
     * Ensures a valid token is present, requesting a new one if expired
     * or refreshing it if we are near to expiration time.
     *
     * @return void
     */
    private function refreshToken()
    {
        if ($this->token->isValid() && ! $this->token->shouldBeRefreshed()) {
            return;
        }

        if (! $this->token->isValid()) {
            $body = http_build_query([
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
            ]);
        } else {
            $body = http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->token->getRefreshToken(),
            ]);
        }

        $request = $this->messageFactory->createRequest('POST', self::AUTH_TOKEN_URI, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
        ], $body);

        $response = $this->client->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            $responseBody = (string)$response->getBody();
            $message = "Response not OK when requesting an access token. Response body: $responseBody";

            throw new InvalidResponseException($response, $message);
        }

        $this->token = Token\Token::fromResponse($response);
        $this->saveToken();
    }

    private function saveToken()
    {
        if (null === $this->cacheDir) {
            return;
        }

        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $fn = $this->cacheDir.DIRECTORY_SEPARATOR.'access_token.json';
        file_put_contents($fn, json_encode($this->token));
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('cache_dir', null);
        $resolver->setRequired([
            'client_id',
            'client_secret',
            'username',
            'password',
        ]);

        return $resolver->resolve($options);
    }
}
