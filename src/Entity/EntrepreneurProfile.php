<?php

namespace App\Entity;

use App\Repository\EntrepreneurProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntrepreneurProfileRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class EntrepreneurProfile
{
    public const array LEGAL_STATUSES = ['SARL', 'SA', 'EI', 'GIE', 'Autre'];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $companyName = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: self::LEGAL_STATUSES)]
    private ?string $legalStatus = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $activitySector = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $registrationNumber = null;

    #[ORM\Column]
    private bool $hasGabonProject = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $investmentSector = null;

    #[ORM\Column]
    private bool $hasBusinessPlan = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Count(max: 4)]
    private ?array $otherTargets = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $businessPlanFile = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLegalStatus(): ?string
    {
        return $this->legalStatus;
    }

    public function setLegalStatus(?string $legalStatus): static
    {
        $this->legalStatus = $legalStatus;

        return $this;
    }

    public function getActivitySector(): ?string
    {
        return $this->activitySector;
    }

    public function setActivitySector(?string $activitySector): static
    {
        $this->activitySector = $activitySector;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): static
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function isHasGabonProject(): bool
    {
        return $this->hasGabonProject;
    }

    public function setHasGabonProject(bool $hasGabonProject): static
    {
        $this->hasGabonProject = $hasGabonProject;

        return $this;
    }

    public function getInvestmentSector(): ?string
    {
        return $this->investmentSector;
    }

    public function setInvestmentSector(?string $investmentSector): static
    {
        $this->investmentSector = $investmentSector;

        return $this;
    }

    public function isHasBusinessPlan(): bool
    {
        return $this->hasBusinessPlan;
    }

    public function setHasBusinessPlan(bool $hasBusinessPlan): static
    {
        $this->hasBusinessPlan = $hasBusinessPlan;

        return $this;
    }

    public function getOtherTargets(): ?array
    {
        return $this->otherTargets;
    }

    public function setOtherTargets(?array $otherTargets): static
    {
        $this->otherTargets = $otherTargets;

        return $this;
    }

    public function getBusinessPlanFile(): ?string
    {
        return $this->businessPlanFile;
    }

    public function setBusinessPlanFile(?string $businessPlanFile): static
    {
        $this->businessPlanFile = $businessPlanFile;

        return $this;
    }
}
