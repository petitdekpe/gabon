<?php

namespace App\Entity;

use App\Repository\AlertLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Trace chaque alerte d'échéance (J-90 / J-30) effectivement envoyée, pour éviter
 * les doublons et fournir un historique consultable.
 */
#[ORM\Entity(repositoryClass: AlertLogRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class AlertLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Registrant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Registrant $registrant;

    /** passport | residentCard | consularCard */
    #[ORM\Column(length: 20)]
    private string $document;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sentAt;

    /** email | sms */
    #[ORM\Column(length: 10)]
    private string $channel;

    public function __construct(Registrant $registrant, string $document, string $channel)
    {
        $this->id = Uuid::v7();
        $this->registrant = $registrant;
        $this->document = $document;
        $this->channel = $channel;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRegistrant(): Registrant
    {
        return $this->registrant;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
