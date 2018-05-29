<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

use Fazland\MailUpRestClient\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MailUp users list.
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class MailingList extends Resource
{
    const OPTOUT_ONE_CLICK = 0;
    const OPTOUT_CONFIRMED = 1;

    const SCOPE_NEWSLETTER = 'newsletters';
    const SCOPE_MARKETING = 'Direct_Advertising';
    const SCOPE_TRANSACTIONAL = 'Transactional';

    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $companyName;

    /**
     * @var string
     */
    private $description;

    /**
     * MailingList constructor.
     * This is private as this object MUST be constructed using its static methods ONLY.
     *
     * @param Context $context
     */
    private function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Constructs a MailingList instance from a MailUp response array.
     *
     * @param Context $context
     * @param array   $response
     *
     * @return MailingList
     */
    private static function fromResponseArray(Context $context, array $response)
    {
        $list = new self($context);
        $list->id = $response['idList'];
        $list->name = $response['Name'];
        $list->companyName = $response['Company'];
        $list->description = $response['Description'];

        return $list;
    }

    /**
     * Gets the list ID (if not new).
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the list's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the list's company name.
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * Gets the list's description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Begin an asynchronous import operation.
     * Returns the import ID.
     *
     * @param Recipient[] $recipients
     * @param bool        $unsubscribe
     * @param bool        $ignoreMobile
     *
     * @return int
     */
    public function import(array $recipients, bool $unsubscribe = false, bool $ignoreMobile = false): int
    {
        $resourceUrl = "/ConsoleService.svc/Console/List/{$this->id}/Recipients";

        $params = [];

        if ($unsubscribe) {
            $params['importType'] = 'asOptout';
        }

        if ($ignoreMobile) {
            $params['ignoreMobile'] = 'true';
        }

        if (0 < count($params)) {
            $resourceUrl .= '?'.http_build_query($params);
        }

        $response = $this->context->makeRequest(
            $resourceUrl,
            'POST',
            $recipients
        );

        return self::getJSON($response);
    }

    /**
     * Adds (and subscribes) the specified {@see Recipient} to current MailUp list.
     *
     * @param Recipient $recipient
     *
     * @return Recipient
     */
    public function addRecipient(Recipient $recipient): Recipient
    {
        $response = $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/$this->id/Recipient",
            'POST',
            $recipient
        );

        $recipient->setId(self::getJSON($response));

        return $recipient;
    }

    /**
     * @deprecated use {@see MailingList::unsubscribeRecipient()} instead
     *
     * @param Recipient $recipient
     */
    public function removeRecipient(Recipient $recipient)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated and will be removed in the first release. Use the unsubscribeRecipient() method instead.', E_USER_DEPRECATED);

        $this->unsubscribeRecipient($recipient);
    }

    /**
     * Unsubscribe the specified {@see Recipient} from current MailUp list.
     *
     * @param Recipient $recipient
     */
    public function unsubscribeRecipient(Recipient $recipient)
    {
        $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/$this->id/Unsubscribe/{$recipient->getId()}",
            'DELETE'
        );
    }

    /**
     * Updates MailUp's {@see Recipient} fields with the ones contained in $recipient.
     *
     * @param Recipient $recipient
     */
    public function updateRecipient(Recipient $recipient)
    {
        $this->context->makeRequest('/ConsoleService.svc/Console/Recipient/Detail', 'PUT', $recipient);
    }

    /**
     * Finds a {@see Recipient} by its email. It returns null if the email could not be found.
     *
     * @param string $email
     *
     * @return Recipient|null
     */
    public function findRecipient(string $email)
    {
        $emailEncoded = urlencode($email);
        $response = $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/{$this->id}/Recipients/Subscribed?filterby=\"Email.Contains(%27$emailEncoded%27)\"",
            'GET'
        );

        $body = self::getJSON($response);

        $recipients = $body['Items'];
        if (! count($recipients)) {
            return null;
        }

        $recipient = Recipient::fromResponseArray($recipients[0]);

        return $recipient;
    }

    /**
     * Gets the groups of the current list.
     *
     * @return ListGroup[]
     */
    public function getGroups(): array
    {
        $response = $this->context->makeRequest("/ConsoleService.svc/Console/List/{$this->id}/Groups", 'GET');
        $body = self::getJSON($response);

        $items = $body['Items'];
        $groups = [];

        foreach ($items as $item) {
            $groups[] = ListGroup::fromResponseArray($this->context, $this, $item);
        }

        return $groups;
    }

    /**
     * Counts the recipient registered in the current list.
     *
     * @param string $subscriptionStatus
     *
     * @return int
     */
    public function countRecipients(string $subscriptionStatus = Recipient::STATUS_SUBSCRIBED): int
    {
        if (! in_array($subscriptionStatus, Recipient::SUBSCRIPTION_STATUSES)) {
            throw new \InvalidArgumentException('Subscription status can be only one of ['.implode(', ', Recipient::SUBSCRIPTION_STATUSES).']!');
        }

        $response = $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/{$this->id}/Recipients/{$subscriptionStatus}",
            'GET'
        );

        $body = self::getJSON($response);

        return $body['TotalElementsCount'] ?? 0;
    }

    /**
     * Gets the list of the recipient paginated by the arguments.
     *
     * @param int    $pageNumber
     * @param int    $pageSize
     * @param string $subscriptionStatus
     *
     * @return Recipient[]
     */
    public function getRecipientsPaginated(
        int $pageNumber,
        int $pageSize,
        string $subscriptionStatus = Recipient::STATUS_SUBSCRIBED
    ): array {
        $results = $this->getRecipientsPaginatedManaged($pageNumber, $pageSize, $subscriptionStatus, false);

        return $results;
    }

    /**
     * @param int    $pageNumber
     * @param int    $pageSize
     * @param string $subscriptionStatus
     * @param bool   $catchExceptions
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getRecipientsPaginatedManaged(
        int $pageNumber,
        int $pageSize,
        string $subscriptionStatus = Recipient::STATUS_SUBSCRIBED,
        bool $catchExceptions = false
    ): array {
        if (! in_array($subscriptionStatus, Recipient::SUBSCRIPTION_STATUSES)) {
            throw new \InvalidArgumentException('Subscription status can be only one of ['.implode(', ', Recipient::SUBSCRIPTION_STATUSES).']!');
        }

        $queryString = http_build_query([
            'PageNumber' => $pageNumber,
            'PageSize' => $pageSize,
        ]);

        $response = $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/{$this->id}/Recipients/{$subscriptionStatus}?$queryString",
            'GET'
        );

        $body = self::getJSON($response);

        $items = $body['Items'];
        $recipients = [];

        foreach ($items as $item) {
            $result = new Result();
            try {
                $recipient = Recipient::fromResponseArray($item);
                $result->setRecipient($recipient);
            } catch (ExceptionInterface $e) {
                if (! $catchExceptions) {
                    throw $e;
                }

                $result->setError($e->getMessage());
            }

            $recipients[] = $result;
        }

        return $recipients;
    }

    /**
     * Gets all MailingList objects contained in the current MailUp account.
     *
     * @param Context $context
     *
     * @return MailingList[]
     */
    public static function getAll(Context $context): array
    {
        $response = $context->makeRequest('/ConsoleService.svc/Console/User/Lists', 'GET');
        $body = self::getJSON($response);

        $items = $body['Items'];

        $lists = [];
        foreach ($items as $item) {
            $lists[] = self::fromResponseArray($context, $item);
        }

        return $lists;
    }

    /**
     * Creates a MailingList.
     *
     * @param Context $context
     * @param string  $name
     * @param string  $ownerEmail
     * @param array   $options
     *
     * @return MailingList
     */
    public static function create(Context $context, string $name, string $ownerEmail, array $options = []): self
    {
        $options = self::resolveCreateOptions($options);
        $params = array_filter([
            'bouncedemail' => $options['bounced_emails_addr'],
            'charset' => $options['charset'],
            'default_prefix' => $options['phone_default_intl_prefix'],
            'description' => $options['description'],
            'disclaimer' => $options['disclaimer'],
            'displayas' => $options['custom_to'],
            'format' => $options['format'],
            'frontendform' => $options['hosted_subscription_form'],
            'headerlistunsubscriber' => $options['list-unsubscribe_header'],
            'headerxabuse' => $options['abuse_report_notice'],
            'kbmax' => 100,
            'multipart_text' => $options['auto_generate_text_part'],
            'nl_sendername' => $options['sender_name'],
            'notifyemail' => $options['unsubscribe_notification_email'],
            'optout_type' => $options['optout_type'],
            'owneremail' => $ownerEmail,
            'public' => false,
            'replyto' => $options['reply_to_addr'],
            'sendconfirmsms' => $options['sms_on_subscription'],
            'subscribedemail' => $options['email_on_subscription'],
            'sendemailoptout' => $options['send_goodbye_mail'],
            'tracking' => $options['enable_tracking'],
            'Customer' => $options['is_customers_list'],
            'business' => $options['is_business_list'],
            'Name' => $name,
            'copyTemplate' => false,
            'copyWebhooks' => false,
            'idSettings' => '',
            'scope' => $options['scope'],
            'useDefaultSettings' => true,
        ], function ($element) {
            return null === $element;
        });

        $response = $context->makeRequest('/ConsoleService.svc/Console/User/Lists', 'GET', $params);
        $id = self::getJSON($response);

        $list = new self($context);
        $list->id = $id;
        $list->name = $name;

        return $list;
    }

    /**
     * Resolves MailingList creation options.
     *
     * @param array $options
     *
     * @return array
     */
    private static function resolveCreateOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'bounced_emails_addr' => null,
            'charset' => 'UTF-8',
            'phone_default_intl_prefix' => null,
            'description' => null,
            'disclaimer' => null,
            'custom_to' => null,
            'format' => 'html',
            'hosted_subscription_form' => false,
            'list-unsubscribe_header' => '<[listunsubscribe]>,<[mailto_uns]>',
            'abuse_report_notice' => 'Please report abuse here: http://[host]/p',
            'auto_generate_text_part' => true,
            'sender_name' => null,
            'unsubscribe_notification_email' => null,
            'optout_type' => self::OPTOUT_ONE_CLICK,
            'reply_to_addr' => null,
            'sms_on_subscription' => false,
            'email_on_subscription' => false,
            'send_goodbye_mail' => false,
            'enable_tracking' => true,
            'is_customers_list' => true,
            'is_business_list' => false,
            'scope' => self::SCOPE_NEWSLETTER,
        ]);

        return $resolver->resolve($options);
    }
}
