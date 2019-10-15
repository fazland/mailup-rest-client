<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

/**
 * MailUp's Recipient representation.
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class Recipient extends Resource implements \JsonSerializable
{
    const STATUS_SUBSCRIBED = 'Subscribed';
    const STATUS_UNSUBSCRIBED = 'Unsubscribed';
    const STATUS_PENDING = 'Pending';

    const SUBSCRIPTION_STATUSES = [
        self::STATUS_SUBSCRIBED,
        self::STATUS_UNSUBSCRIBED,
        self::STATUS_PENDING,
    ];

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
     *
     * @param string         $name
     * @param string         $email
     * @param string|null    $mobilePhone
     * @param string|null    $mobilePrefix
     * @param DynamicField[] $fields
     */
    public function __construct(
        string $name,
        string $email,
        string $mobilePhone = null,
        string $mobilePrefix = null,
        array $fields = []
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->mobileNumber = $mobilePhone;
        $this->mobilePrefix = $mobilePrefix;
        $this->fields = $fields;
    }

    /**
     * Gets the id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id.
     *
     * @param int $id
     *
     * @return $this|self
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Gets the mobile number.
     *
     * @return string|null
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * Gets the mobile prefix.
     *
     * @return string|null
     */
    public function getMobilePrefix()
    {
        return $this->mobilePrefix;
    }

    /**
     * Gets the dynamic fields.
     *
     * @return DynamicField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Sets the dynamic fields.
     *
     * @param DynamicField[] $fields
     *
     * @return $this|self
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Constructs a Recipient instance from a MailUp response array.
     *
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
            \iterator_to_array($toFields($response['Fields']))
        );

        $recipient->setId($response['idRecipient']);

        return $recipient;
    }

    /**
     * {@inheritdoc}
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

    /**
     * Gets the dynamic fields.
     *
     * @param Context $context
     *
     * @return DynamicField[]
     */
    public static function getDynamicFields(Context $context): array
    {
        $response = $context->makeRequest('/ConsoleService.svc/Console/Recipient/DynamicFields', 'GET');
        $body = self::getJSON($response);

        $fields = [];
        foreach ($body['Items'] as $item) {
            $fields[] = DynamicField::fromResponseArray($item);
        }

        return $fields;
    }
}
