<?php declare(strict_types=1);

namespace Fazland\MailUpRestClient;

class Import extends Resource
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var bool
     */
    private $completed;

    /**
     * @var int
     */
    private $createdRecipients;

    /**
     * @var int
     */
    private $importedRecipients;

    /**
     * @var int
     */
    private $notValidRecipients;

    /**
     * @var int
     */
    private $updatedRecipients;

    /**
     * @var int
     */
    private $validRecipients;

    /**
     * Private Import constructor.
     * Instances can only be obtained via the {@see Import::retrieve()} method.
     */
    private function __construct()
    {
    }

    /**
     * Retrieves the import status by its identifier.
     *
     * @param Context $context
     * @param int     $importId
     *
     * @return self
     */
    public static function retrieve(Context $context, int $importId): self
    {
        $response = $context->makeRequest("/ConsoleService.svc/Console/Import/$importId", 'GET');

        $json = self::getJSON($response);

        $instance = new self();
        $instance->id = $json['idImport'];
        $instance->completed = $json['Completed'];
        $instance->createdRecipients = $json['CreatedRecipients'];
        $instance->importedRecipients = $json['ImportedRecipients'];
        $instance->notValidRecipients = $json['NotValidRecipients'];
        $instance->updatedRecipients = $json['UpdatedRecipients'];
        $instance->validRecipients = $json['ValidRecipients'];

        return $instance;
    }

    /**
     * Whether the import is completed or not.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * Gets how many recipients have been created.
     *
     * @return int
     */
    public function getCreatedRecipients(): int
    {
        return $this->createdRecipients;
    }

    /**
     * Gets how many recipients have been imported.
     *
     * @return int
     */
    public function getImportedRecipients(): int
    {
        return $this->importedRecipients;
    }

    /**
     * Gets how many recipients could not be imported.
     *
     * @return int
     */
    public function getNotValidRecipients(): int
    {
        return $this->notValidRecipients;
    }

    /**
     * Gets how many recipients have been updated.
     *
     * @return int
     */
    public function getUpdatedRecipients(): int
    {
        return $this->updatedRecipients;
    }

    /**
     * Gets how many recipients were valid (and imported).
     *
     * @return int
     */
    public function getValidRecipients(): int
    {
        return $this->validRecipients;
    }
}
