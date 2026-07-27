<?php

namespace App\Entity;

use App\Entity\Enum\AfricanCountry;
use App\Entity\Enum\ProfileType;
use App\Entity\Enum\RegistrantStatus;
use App\Repository\RegistrantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RegistrantRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class Registrant
{
    private const string PHONE_E164_REGEX = '/^\+[1-9]\d{1,14}$/';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 2)]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [AfricanCountry::class, 'codes'])]
    private string $country = 'BJ';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    #[Assert\LessThan(value: '-16 years')]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $birthPlace = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $birthCountry = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $profession = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: self::PHONE_E164_REGEX, message: 'Le numéro doit être au format international E.164 (ex. +22912345678).')]
    private ?string $localPhone = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: self::PHONE_E164_REGEX, message: 'Le numéro doit être au format international E.164 (ex. +24101234567).')]
    private ?string $gabonContact = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $firstEntryDate = null;

    #[ORM\Column(length: 20, enumType: ProfileType::class)]
    #[Assert\NotBlank]
    private ?ProfileType $profileType = null;

    #[ORM\Column(length: 20, enumType: RegistrantStatus::class)]
    private RegistrantStatus $status = RegistrantStatus::Draft;

    #[ORM\Column]
    #[Assert\IsTrue]
    private bool $consentGiven = false;

    #[ORM\OneToOne(targetEntity: IdentityDocument::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Assert\Valid]
    private IdentityDocument $identityDocument;

    #[ORM\OneToOne(targetEntity: StudentProfile::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, unique: true)]
    #[Assert\Valid]
    private ?StudentProfile $studentProfile = null;

    #[ORM\OneToOne(targetEntity: EmployeeProfile::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, unique: true)]
    #[Assert\Valid]
    private ?EmployeeProfile $employeeProfile = null;

    #[ORM\OneToOne(targetEntity: EntrepreneurProfile::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, unique: true)]
    #[Assert\Valid]
    private ?EntrepreneurProfile $entrepreneurProfile = null;

    public function __construct(IdentityDocument $identityDocument)
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->identityDocument = $identityDocument;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBirthPlace(): ?string
    {
        return $this->birthPlace;
    }

    public function setBirthPlace(?string $birthPlace): static
    {
        $this->birthPlace = $birthPlace;

        return $this;
    }

    public function getBirthCountry(): ?string
    {
        return $this->birthCountry;
    }

    public function setBirthCountry(?string $birthCountry): static
    {
        $this->birthCountry = $birthCountry;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getLocalPhone(): ?string
    {
        return $this->localPhone;
    }

    public function setLocalPhone(?string $localPhone): static
    {
        $this->localPhone = $localPhone;

        return $this;
    }

    public function getGabonContact(): ?string
    {
        return $this->gabonContact;
    }

    public function setGabonContact(?string $gabonContact): static
    {
        $this->gabonContact = $gabonContact;

        return $this;
    }

    public function getFirstEntryDate(): ?\DateTimeImmutable
    {
        return $this->firstEntryDate;
    }

    public function setFirstEntryDate(?\DateTimeImmutable $firstEntryDate): static
    {
        $this->firstEntryDate = $firstEntryDate;

        return $this;
    }

    public function getProfileType(): ?ProfileType
    {
        return $this->profileType;
    }

    public function setProfileType(?ProfileType $profileType): static
    {
        $this->profileType = $profileType;

        return $this;
    }

    public function getStatus(): RegistrantStatus
    {
        return $this->status;
    }

    public function setStatus(RegistrantStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isConsentGiven(): bool
    {
        return $this->consentGiven;
    }

    public function setConsentGiven(bool $consentGiven): static
    {
        $this->consentGiven = $consentGiven;

        return $this;
    }

    public function getIdentityDocument(): IdentityDocument
    {
        return $this->identityDocument;
    }

    public function setIdentityDocument(IdentityDocument $identityDocument): static
    {
        $this->identityDocument = $identityDocument;

        return $this;
    }

    public function getStudentProfile(): ?StudentProfile
    {
        return $this->studentProfile;
    }

    public function setStudentProfile(?StudentProfile $studentProfile): static
    {
        $this->studentProfile = $studentProfile;

        return $this;
    }

    public function getEmployeeProfile(): ?EmployeeProfile
    {
        return $this->employeeProfile;
    }

    public function setEmployeeProfile(?EmployeeProfile $employeeProfile): static
    {
        $this->employeeProfile = $employeeProfile;

        return $this;
    }

    public function getEntrepreneurProfile(): ?EntrepreneurProfile
    {
        return $this->entrepreneurProfile;
    }

    public function setEntrepreneurProfile(?EntrepreneurProfile $entrepreneurProfile): static
    {
        $this->entrepreneurProfile = $entrepreneurProfile;

        return $this;
    }
}
