<?php

namespace Fazland\MailUpRestClient;


class Result
{
    /**
     * @var null|Recipient
     */
    private $recipient;

    /**
     * @var null|string
     */
    private $error;

    /**
     * Result constructor.
     *
     * @param null|Recipient $recipient
     * @param null|string $error
     */
    public function __construct($recipient = null, $error = null)
    {
        $this->recipient = $recipient;
        $this->error     = $error;
    }

    /**
     * @return Recipient|null
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param Recipient|null $recipient
     *
     * @return $this
     */
    public function setRecipient($recipient): Result
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param null|string $error
     *
     * @return $this
     */
    public function setError($error): Result
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return null !== $this->error;
    }
}