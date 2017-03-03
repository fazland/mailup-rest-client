<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Token;

/**
 * Represents the MailUP access/refresh token pair
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
interface TokenInterface extends \JsonSerializable
{
    /**
     * Is token still valid?
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Checks whether to refresh the token
     *
     * @return bool
     */
    public function shouldBeRefreshed(): bool;

    /**
     * Gets the access token, even if expired.
     *
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * Gets the refresh token
     *
     * @return string
     */
    public function getRefreshToken(): string;
}
