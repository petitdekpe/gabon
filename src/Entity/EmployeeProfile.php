<?php

namespace App\Entity;

use App\Repository\EmployeeProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeeProfileRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class EmployeeProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lastDegree = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1950)]
    private ?int $firstJobYear = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $activitySector = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $currentCompany = null;

    #[ORM\Column]
    private bool $wishIntegrateGabon = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetCompany = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Count(max: 4)]
    private ?array $otherCompanies = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFile = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLastDegree(): ?string
    {
        return $this->lastDegree;
    }

    public function setLastDegree(?string $lastDegree): static
    {
        $this->lastDegree = $lastDegree;

        return $this;
    }

    public function getFirstJobYear(): ?int
    {
        return $this->firstJobYear;
    }

    public function setFirstJobYear(?int $firstJobYear): static
    {
        $this->firstJobYear = $firstJobYear;

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

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getCurrentCompany(): ?string
    {
        return $this->currentCompany;
    }

    public function setCurrentCompany(?string $currentCompany): static
    {
        $this->currentCompany = $currentCompany;

        return $this;
    }

    public function isWishIntegrateGabon(): bool
    {
        return $this->wishIntegrateGabon;
    }

    public function setWishIntegrateGabon(bool $wishIntegrateGabon): static
    {
        $this->wishIntegrateGabon = $wishIntegrateGabon;

        return $this;
    }

    public function getTargetCompany(): ?string
    {
        return $this->targetCompany;
    }

    public function setTargetCompany(?string $targetCompany): static
    {
        $this->targetCompany = $targetCompany;

        return $this;
    }

    public function getOtherCompanies(): ?array
    {
        return $this->otherCompanies;
    }

    public function setOtherCompanies(?array $otherCompanies): static
    {
        $this->otherCompanies = $otherCompanies;

        return $this;
    }

    public function getCvFile(): ?string
    {
        return $this->cvFile;
    }

    public function setCvFile(?string $cvFile): static
    {
        $this->cvFile = $cvFile;

        return $this;
    }
}
