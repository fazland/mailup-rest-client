<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

/**
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

    public function __construct(string $fieldName, string $value, int $id)
    {
        $this->fieldName = $fieldName;
        $this->value     = $value;
        $this->id        = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
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
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'Description' => $this->fieldName,
            'Id'          => $this->id,
            'Value'       => $this->value,
        ];
    }
}
