<?php

namespace AppBundle\Entity;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Mailjet\Message\MailjetMessage;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="mailjet_emails")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MailjetEmailRepository")
 *
 * @Algolia\Index(autoIndex=false)
 */
class MailjetEmail
{
    use EntityIdentityTrait;
    use EntityTimestampableTrait;

    /**
     * The Mailjet message class namespace.
     *
     * @ORM\Column(length=50, nullable=true)
     */
    private $messageClass;

    /**
     * @ORM\Column(length=100)
     */
    private $sender;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $recipients;

    /**
     * The API request JSON payload.
     *
     * @ORM\Column(type="text")
     */
    private $requestPayload;

    /**
     * The successful API response JSON payload.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $responsePayload;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deliveredAt;

    public function __construct(UuidInterface $uuid, string $messageClass, string $sender, array $recipients, string $requestPayload)
    {
        $this->uuid = $uuid;
        $this->messageClass = $messageClass;
        $this->sender = $sender;
        $this->recipients = $recipients;
        $this->requestPayload = base64_encode($requestPayload);
    }

    public function __toString(): string
    {
        return $this->messageClass.' de '.$this->sender.' à '.count($this->recipients).' destinataires';
    }

    public function getEnglishLog(): string
    {
        return $this->messageClass.' from '.$this->sender.' to '.count($this->recipients).' recipients';
    }

    public static function createFromMessage(MailjetMessage $message, $requestPayload): self
    {
        $recipients = [];

        foreach ($message->getRecipients() as $recipient) {
            $recipients[] = $recipient->getFullName().' <'.$recipient->getEmailAddress().'>';
        }

        return new self(
            $message->getUuid(),
            str_replace('AppBundle\\Mailjet\\Message\\', '', get_class($message)),
            $message->getReplyTo() ?? 'EnMarche',
            $recipients,
            $requestPayload
        );
    }

    public function delivered(?string $responsePayload): void
    {
        $this->responsePayload = base64_encode($responsePayload);
        $this->deliveredAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageClass(): string
    {
        return $this->messageClass;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getRecipientsAsString(): string
    {
        return implode("\n", $this->recipients);
    }

    public function getRequestPayload(): ?array
    {
        return $this->requestPayload ? json_decode(base64_decode($this->requestPayload), true) : null;
    }

    public function getRequestPayloadJson(): ?string
    {
        return $this->requestPayload ? base64_decode($this->requestPayload) : null;
    }

    public function getResponsePayload(): ?array
    {
        return $this->responsePayload ? json_decode(base64_decode($this->responsePayload), true) : null;
    }

    public function getResponsePayloadJson(): ?string
    {
        return $this->responsePayload ? base64_decode($this->responsePayload) : null;
    }

    public function isDelivered(): bool
    {
        return $this->responsePayload !== null;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getDeliveredAt(): ?\DateTime
    {
        return $this->deliveredAt;
    }
}
