<?php

namespace App\Entity;

use App\Repository\IdentityDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: IdentityDocumentRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class IdentityDocument
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $passportNumber = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $passportIssuedAt = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $passportExpiresAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $residentCardNumber = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $residentCardIssuedAt = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $residentCardExpiresAt = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $consularCardNumber = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $consularCardIssuedAt = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $consularCardExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passportFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $residentCardFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $consularCardFile = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function setPassportNumber(?string $passportNumber): static
    {
        $this->passportNumber = $passportNumber;

        return $this;
    }

    public function getPassportIssuedAt(): ?\DateTimeImmutable
    {
        return $this->passportIssuedAt;
    }

    public function setPassportIssuedAt(?\DateTimeImmutable $passportIssuedAt): static
    {
        $this->passportIssuedAt = $passportIssuedAt;

        return $this;
    }

    public function getPassportExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passportExpiresAt;
    }

    public function setPassportExpiresAt(?\DateTimeImmutable $passportExpiresAt): static
    {
        $this->passportExpiresAt = $passportExpiresAt;

        return $this;
    }

    public function getResidentCardNumber(): ?string
    {
        return $this->residentCardNumber;
    }

    public function setResidentCardNumber(?string $residentCardNumber): static
    {
        $this->residentCardNumber = $residentCardNumber;

        return $this;
    }

    public function getResidentCardIssuedAt(): ?\DateTimeImmutable
    {
        return $this->residentCardIssuedAt;
    }

    public function setResidentCardIssuedAt(?\DateTimeImmutable $residentCardIssuedAt): static
    {
        $this->residentCardIssuedAt = $residentCardIssuedAt;

        return $this;
    }

    public function getResidentCardExpiresAt(): ?\DateTimeImmutable
    {
        return $this->residentCardExpiresAt;
    }

    public function setResidentCardExpiresAt(?\DateTimeImmutable $residentCardExpiresAt): static
    {
        $this->residentCardExpiresAt = $residentCardExpiresAt;

        return $this;
    }

    public function getConsularCardNumber(): ?string
    {
        return $this->consularCardNumber;
    }

    public function setConsularCardNumber(?string $consularCardNumber): static
    {
        $this->consularCardNumber = $consularCardNumber;

        return $this;
    }

    public function getConsularCardIssuedAt(): ?\DateTimeImmutable
    {
        return $this->consularCardIssuedAt;
    }

    public function setConsularCardIssuedAt(?\DateTimeImmutable $consularCardIssuedAt): static
    {
        $this->consularCardIssuedAt = $consularCardIssuedAt;

        return $this;
    }

    public function getConsularCardExpiresAt(): ?\DateTimeImmutable
    {
        return $this->consularCardExpiresAt;
    }

    public function setConsularCardExpiresAt(?\DateTimeImmutable $consularCardExpiresAt): static
    {
        $this->consularCardExpiresAt = $consularCardExpiresAt;

        return $this;
    }

    public function getPassportFile(): ?string
    {
        return $this->passportFile;
    }

    public function setPassportFile(?string $passportFile): static
    {
        $this->passportFile = $passportFile;

        return $this;
    }

    public function getResidentCardFile(): ?string
    {
        return $this->residentCardFile;
    }

    public function setResidentCardFile(?string $residentCardFile): static
    {
        $this->residentCardFile = $residentCardFile;

        return $this;
    }

    public function getConsularCardFile(): ?string
    {
        return $this->consularCardFile;
    }

    public function setConsularCardFile(?string $consularCardFile): static
    {
        $this->consularCardFile = $consularCardFile;

        return $this;
    }

    /**
     * La carte de résident est optionnelle ("Non concerné"), mais si l'un des trois champs
     * est renseigné, les trois doivent l'être : pas de saisie partielle.
     */
    #[Assert\Callback]
    public function validateResidentCardCompleteness(ExecutionContextInterface $context): void
    {
        $fields = [$this->residentCardNumber, $this->residentCardIssuedAt, $this->residentCardExpiresAt];
        $filled = array_filter($fields, static fn (mixed $value): bool => $value !== null && $value !== '');

        if (count($filled) > 0 && count($filled) < count($fields)) {
            $context->buildViolation('Renseignez le numéro et les deux dates de la carte de résident, ou cochez "Non concerné" si elle ne s\'applique pas.')
                ->atPath('residentCardNumber')
                ->addViolation();
        }
    }
}
