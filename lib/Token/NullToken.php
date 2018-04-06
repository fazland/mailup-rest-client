<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Token;

/**
 * Always invalid token.
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
final class NullToken implements TokenInterface
{
    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeRefreshed(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken(): string
    {
        throw new \LogicException('Invalid call');
    }

    /**
     * {@inheritdoc}
     */
    public function getRefreshToken(): string
    {
        throw new \LogicException('Invalid call');
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
