<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

/**
 * Represents the dynamic fields of a {@see Recipient}.
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
final class DynamicField implements \JsonSerializable
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $id;

    /**
     * DynamicField constructor.
     *
     * @param string $fieldName
     * @param string $value
     * @param int    $id
     */
    public function __construct(string $fieldName, string $value, int $id)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->id = $id;
    }

    /**
     * Gets the id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the field name.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Gets the field value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Constructs a DynamicField instance from a MailUp response array.
     *
     * @param array $response
     *
     * @return DynamicField
     */
    public static function fromResponseArray(array $response): self
    {
        return new self(
            $response['Description'],
            '',
            $response['Id']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'Description' => $this->fieldName,
            'Id' => $this->id,
            'Value' => $this->value,
        ];
    }
}
