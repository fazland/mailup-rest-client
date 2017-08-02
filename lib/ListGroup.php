<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

use Fazland\MailUpRestClient\Exception\CannotDeleteGroupException;

/**
 * Group of users in a list
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class ListGroup extends Resource
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $id;

    /**
     * @var MailingList
     */
    private $list;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $notes;

    /**
     * @var bool
     */
    private $deletable;

    /**
     * ListGroup constructor.
     *
     * @param Context $context
     * @param MailingList $list
     * @param int $id
     *
     * @internal
     */
    public function __construct(Context $context, MailingList $list, int $id)
    {
        $this->context = $context;
        $this->id = $id;
        $this->list = $list;
    }

    /**
     * @param Context $context
     * @param MailingList $list
     * @param array $response
     *
     * @return self
     */
    public static function fromResponseArray(Context $context, MailingList $list, array $response): self
    {
        $group = new self($context, $list, $response['idGroup']);
        $group
            ->setName($response['Name'])
            ->setNotes($response['Notes'])
            ->setDeletable($response['Deletable'])
        ;

        return $group;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this|self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return $this|self
     */
    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     *
     * @return $this|self
     */
    public function setDeletable(bool $deletable): self
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Deletes the group from the list.
     *
     * @throws CannotDeleteGroupException
     */
    public function delete()
    {
        if (! $this->deletable) {
            throw new CannotDeleteGroupException("$this->name group is not deletable");
        }

        $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/{$this->list->getId()}/Group/{$this->id}",
            'DELETE'
        );
    }

    /**
     * Subscribes the specified {@see Recipient} to current MailUp group.
     *
     * @param Recipient $recipient
     *
     * @return Recipient
     */
    public function addRecipient(Recipient $recipient)
    {
        $response = $this->context->makeRequest(
            "/ConsoleService.svc/Console/Group/$this->id/Recipient",
            'POST',
            $recipient
        );

        $recipient->setId(self::getJSON($response));

        return $recipient;
    }

    /**
     * Unsubscribe the specified {@see Recipient} from current MailUp group.
     *
     * @param Recipient $recipient
     */
    public function removeRecipient(Recipient $recipient)
    {
        $this->context->makeRequest(
            "/ConsoleService.svc/Console/Group/$this->id/Unsubscribe/{$recipient->getId()}",
            'DELETE'
        );
    }

    /**
     * Gets the {@see Recipient} array
     *
     * @return array
     */
    public function getRecipients(): array
    {
        $response = $this->context->makeRequest("ConsoleService.svc/Console/Group/{$this->id}/Recipient", 'GET');

        $body = self::getJSON($response);

        $items = $body['Items'];
        if (! count($items)) {
            return null;
        }

        $recipients = [];
        foreach ($items as $item) {
            $recipients[] = Recipient::fromResponseArray($item);
        }

        return $recipients;
    }
}
