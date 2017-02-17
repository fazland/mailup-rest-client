<?php

declare(strict_types=1);

namespace Fazland\MailUpRestClient;

/**
 * MailUp's Recipient representation
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class Recipient extends Resource implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $mobileNumber;

    /**
     * @var string
     */
    private $mobilePrefix;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var int
     */
    private $id;

    /**
     * Recipient constructor.
     * @param string $name
     * @param string $email
     * @param string $mobilePhone
     * @param string $mobilePrefix
     * @param array $fields
     */
    public function __construct(
        string $name,
        string $email,
        string $mobilePhone,
        string $mobilePrefix,
        array $fields = []
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->mobileNumber = $mobilePhone;
        $this->mobilePrefix = $mobilePrefix;
        $this->fields = $fields;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getMobileNumber(): string
    {
        return $this->mobileNumber;
    }

    /**
     * @return string
     */
    public function getMobilePrefix(): string
    {
        return $this->mobilePrefix;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param array $response
     *
     * @return Recipient
     */
    public static function fromResponseArray(array $response): self
    {
        $toFields = function (array $fields) {
            foreach ($fields as $field) {
                yield new DynamicField($field['Description'], $field['Value'], $field['Id']);
            }
        };

        $recipient = new self(
            $response['Name'],
            $response['Email'],
            $response['MobileNumber'],
            $response['MobilePrefix'],
            iterator_to_array($toFields($response['Fields']))
        );

        $recipient->setId($response['idRecipient']);

        return $recipient;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $recipient = [
            'Name' => $this->name,
            'Email' => $this->email,
            'MobileNumber' => $this->mobileNumber,
            'MobilePrefix' => $this->mobilePrefix,
            'Fields' => $this->fields,
        ];

        if (null !== $this->id) {
            $recipient['idRecipient'] = $this->id;
        }

        return $recipient;
    }
}
