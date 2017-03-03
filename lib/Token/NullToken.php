<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient\Token;

/**
 * Always invalid token
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 */
final class NullToken implements TokenInterface
{
    /**
     * @inheritDoc
     */
    public function isValid() : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeRefreshed() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken() : string
    {
        throw new \LogicException('Invalid call');
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken() : string
    {
        throw new \LogicException('Invalid call');
    }

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        return [];
    }
}
