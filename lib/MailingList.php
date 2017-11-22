<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MailUp users list
 *
 * @author Alessandro Chitolina <alessandro.chitolina@fazland.com>
 * @author Massimiliano Braglia <massimiliano.braglia@fazland.com>
 */
class MailingList extends Resource
{
    const OPTOUT_ONE_CLICK = 0;
    const OPTOUT_CONFIRMED = 1;

    const SCOPE_NEWSLETTER    = 'newsletters';
    const SCOPE_MARKETING     = 'Direct_Advertising';
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
     * This is private as this object MUST be constructed
     * using its static methods ONLY
     *
     * @param Context $context
     */
    private function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param Context $context
     * @param array $response
     *
     * @return MailingList
     */
    private static function fromResponseArray(Context $context, array $response)
    {
        $list = new self($context);
        $list->id          = $response['idList'];
        $list->name        = $response['Name'];
        $list->companyName = $response['Company'];
        $list->description = $response['Description'];

        return $list;
    }

    /**
     * Gets the list ID (if not new)
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the list's name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the list's company name
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * Gets the list's description
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
     *
     * @return int
     */
    public function import(array $recipients): int
    {
        $response = $this->context->makeRequest(
                        "/ConsoleService.svc/Console/List/{$this->id}".
                        "/Recipients",
                        'POST',
                        $recipients
                    );

        return self::getJSON($response);
    }

    /**
     * Subscribes the specified {@see Recipient} to current MailUp list.
     *
     * @param Recipient $recipient
     * @param bool      $confirmByEmail    Enable Confirmed Opt-in
     *                                     (required for resubscribing recipients)
     *
     * @return Recipient
     */
    public function addRecipient(
        Recipient $recipient,
        bool $confirmByEmail = false
    ): Recipient {
        $queryString = http_build_query([
            'ConfirmEmail' => var_export($confirmByEmail, true),
        ]);

        $response = $this->context->makeRequest(
                        "/ConsoleService.svc/Console/List/{$this->id}".
                        "/Recipient".
                        "?{$queryString}",
                        'POST',
                        $recipient
                    );

        $recipient->setId(self::getJSON($response));

        return $recipient;
    }

    /**
     * Unsubscribe the specified {@see Recipient} from current MailUp list.
     *
     * @param Recipient $recipient
     */
    public function removeRecipient(Recipient $recipient)
    {
        $this->context->makeRequest(
            "/ConsoleService.svc/Console/List/{$this->id}".
            "/Unsubscribe/{$recipient->getId()}",
            'DELETE'
        );
    }

    /**
     * Updates MailUp's {@see Recipient}.
     *
     * @param Recipient $recipient
     */
    public function updateRecipient(Recipient $recipient)
    {
        $this->context->makeRequest(
            "/ConsoleService.svc/Console/Recipient/Detail",
            'PUT',
            $recipient
        );
    }

    /**
     * @param string $email
     * @param string $status
     *
     * @return Recipient|null
     */
    public function findRecipient(
        string $email,
        string $status = Recipient::STATUS_SUBSCRIBED
    ) {
        if (
               Recipient::STATUS_ANY !== $status
            && ! in_array($status, Recipient::SUBSCRIPTION_STATUSES)
        ) {
            throw new \InvalidArgumentException(
                "Subscription status should be ".
                "'" . implode("', '", Recipient::SUBSCRIPTION_STATUSES) . "' ".
                "or '" . Recipient::STATUS_ANY . "'."
            );
        }

        $statuses = Recipient::STATUS_ANY === $status
                  ? Recipient::SUBSCRIPTION_STATUSES
                  : [$status];

        $emailEncoded = urlencode($email);
        $queryString  = "filterby=Email.Contains(%27{$emailEncoded}%27)";

        $recipients = [];
        foreach ($statuses as $status) {
            $path = "/ConsoleService.svc/Console/List/{$this->id}"
                  . "/Recipients/{$status}"
                  . "?{$queryString}";

            $response   = $this->context->makeRequest($path, 'GET');
            $body       = self::getJSON($response);
            $recipients = array_merge($recipients, $body['Items']);
        }

        if (! count($recipients)) {
            return null;
        }

        return Recipient::fromResponseArray($recipients[0]);
    }

    /**
     * @return ListGroup[]
     */
    public function getGroups(): array
    {
        $response = $this->context->makeRequest(
                        "/ConsoleService.svc/Console/List/{$this->id}".
                        "/Groups",
                        'GET'
                    );
        $body = self::getJSON($response);

        $groups = [];
        foreach ($body['Items'] as $item) {
            $groups[] = ListGroup::fromResponseArray(
                            $this->context,
                            $this,
                            $item
                        );
        }

        return $groups;
    }

    /**
     * @param string $status
     *
     * @return int
     */
    public function countRecipients(
        string $status = Recipient::STATUS_SUBSCRIBED
    ): int {
        if (
               Recipient::STATUS_ANY !== $status
            && ! in_array($status, Recipient::SUBSCRIPTION_STATUSES)
        ) {
            throw new \InvalidArgumentException(
                "Subscription status should be ".
                "'" . implode("', '", Recipient::SUBSCRIPTION_STATUSES) . "' ".
                "or '" . Recipient::STATUS_ANY . "'."
            );
        }

        $statuses = Recipient::STATUS_ANY === $status
                  ? Recipient::SUBSCRIPTION_STATUSES
                  : [$status];

        $count = 0;
        foreach ($statuses as $status) {
            $path = "/ConsoleService.svc/Console/List/{$this->id}"
                  . "/Recipients/{$status}";

            $response  = $this->context->makeRequest($path, 'GET');
            $body      = self::getJSON($response);
            $count    += $body['TotalElementsCount'] ?? 0;
        }

        return $count;
    }

    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $status
     *
     * @return Recipient[]
     */
    public function getRecipientsPaginated(
        int $pageNumber,
        int $pageSize,
        string $status = Recipient::STATUS_SUBSCRIBED
    ): array {
        if (! in_array($status, Recipient::SUBSCRIPTION_STATUSES)) {
            $statuses = Recipient::SUBSCRIPTION_STATUSES;
            $lastStatus = array_pop($statuses);

            throw new \InvalidArgumentException(
                "Subscription status should be ".
                "'" . implode("', '", $statuses) . "' ".
                "or '" . $lastStatus . "'."
            );
        }

        $queryString = http_build_query([
            'PageNumber' => $pageNumber,
            'PageSize'   => $pageSize,
        ]);

        $recipients = [];
        foreach (Recipient::SUBSCRIPTION_STATUSES as $status) {
            $path = "/ConsoleService.svc/Console/List/{$this->id}"
                  . "/Recipients/{$status}"
                  . "?{$queryString}";

            $response = $this->context->makeRequest($path, 'GET');
            $body     = self::getJSON($response);
            foreach ($body['Items'] as $item) {
                $recipients[] = Recipient::fromResponseArray($item);
            }
        }

        return $recipients;
    }

    /**
     * @param Context $context
     *
     * @return MailingList[]
     */
    public static function getAll(Context $context): array
    {
        $response = $context->makeRequest(
            '/ConsoleService.svc/Console/User/Lists',
            'GET'
        );
        $body = self::getJSON($response);

        $lists = [];
        foreach ($body['Items'] as $item) {
            $lists[] = self::fromResponseArray($context, $item);
        }

        return $lists;
    }

    /**
     * @param Context $context
     * @param string $name
     * @param string $ownerEmail
     * @param array $options
     *
     * @return MailingList
     */
    public static function create(
        Context $context,
        string $name,
        string $ownerEmail,
        array $options = []
    ): self {
        $options = self::resolveCreateOptions($options);
        $params = array_filter([
            'bouncedemail'           => $options['bounced_emails_addr'],
            'charset'                => $options['charset'],
            'default_prefix'         => $options['phone_default_intl_prefix'],
            'description'            => $options['description'],
            'disclaimer'             => $options['disclaimer'],
            'displayas'              => $options['custom_to'],
            'format'                 => $options['format'],
            'frontendform'           => $options['hosted_subscription_form'],
            'headerlistunsubscriber' => $options['list-unsubscribe_header'],
            'headerxabuse'           => $options['abuse_report_notice'],
            'kbmax'                  => 100,
            'multipart_text'         => $options['auto_generate_text_part'],
            'nl_sendername'          => $options['sender_name'],
            'notifyemail'            => $options['unsubscribe_notification_email'],
            'optout_type'            => $options['optout_type'],
            'owneremail'             => $ownerEmail,
            'public'                 => false,
            'replyto'                => $options['reply_to_addr'],
            'sendconfirmsms'         => $options['sms_on_subscription'],
            'subscribedemail'        => $options['email_on_subscription'],
            'sendemailoptout'        => $options['send_goodbye_mail'],
            'tracking'               => $options['enable_tracking'],
            'Customer'               => $options['is_customers_list'],
            'business'               => $options['is_business_list'],
            'Name'                   => $name,
            'copyTemplate'           => false,
            'copyWebhooks'           => false,
            'idSettings'             => '',
            'scope'                  => $options['scope'],
            'useDefaultSettings'     => true
        ], function ($element) {
            return null === $element;
        });

        $response = $context->makeRequest(
            '/ConsoleService.svc/Console/User/Lists',
            'GET',
            $params
        );
        $id = self::getJSON($response);

        $list = new self($context);
        $list->id   = $id;
        $list->name = $name;

        return $list;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private static function resolveCreateOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'bounced_emails_addr'            => null,
            'charset'                        => 'UTF-8',
            'phone_default_intl_prefix'      => null,
            'description'                    => null,
            'disclaimer'                     => null,
            'custom_to'                      => null,
            'format'                         => 'html',
            'hosted_subscription_form'       => false,
            'list-unsubscribe_header'        => '<[listunsubscribe]>,<[mailto_uns]>',
            'abuse_report_notice'            => 'Please report abuse here: http://[host]/p',
            'auto_generate_text_part'        => true,
            'sender_name'                    => null,
            'unsubscribe_notification_email' => null,
            'optout_type'                    => self::OPTOUT_ONE_CLICK,
            'reply_to_addr'                  => null,
            'sms_on_subscription'            => false,
            'email_on_subscription'          => false,
            'send_goodbye_mail'              => false,
            'enable_tracking'                => true,
            'is_customers_list'              => true,
            'is_business_list'               => false,
            'scope'                          => self::SCOPE_NEWSLETTER,
        ]);

        return $resolver->resolve($options);
    }
}
