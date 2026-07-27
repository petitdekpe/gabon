<?php

namespace App\Entity;

use App\Repository\StudentProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentProfileRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
class StudentProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column]
    private bool $inTraining = false;

    #[ORM\Column]
    private bool $endOfCycle = false;

    #[ORM\Column]
    private bool $awaitingDefense = false;

    #[ORM\Column]
    private bool $graduatedJobSeeking = false;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $institution = null;

    #[ORM\Column]
    private bool $wishReturnGabon = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetAdministration = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Count(max: 4)]
    private ?array $otherAdministrations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplomaFile = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function isInTraining(): bool
    {
        return $this->inTraining;
    }

    public function setInTraining(bool $inTraining): static
    {
        $this->inTraining = $inTraining;

        return $this;
    }

    public function isEndOfCycle(): bool
    {
        return $this->endOfCycle;
    }

    public function setEndOfCycle(bool $endOfCycle): static
    {
        $this->endOfCycle = $endOfCycle;

        return $this;
    }

    public function isAwaitingDefense(): bool
    {
        return $this->awaitingDefense;
    }

    public function setAwaitingDefense(bool $awaitingDefense): static
    {
        $this->awaitingDefense = $awaitingDefense;

        return $this;
    }

    public function isGraduatedJobSeeking(): bool
    {
        return $this->graduatedJobSeeking;
    }

    public function setGraduatedJobSeeking(bool $graduatedJobSeeking): static
    {
        $this->graduatedJobSeeking = $graduatedJobSeeking;

        return $this;
    }

    public function getInstitution(): ?string
    {
        return $this->institution;
    }

    public function setInstitution(?string $institution): static
    {
        $this->institution = $institution;

        return $this;
    }

    public function isWishReturnGabon(): bool
    {
        return $this->wishReturnGabon;
    }

    public function setWishReturnGabon(bool $wishReturnGabon): static
    {
        $this->wishReturnGabon = $wishReturnGabon;

        return $this;
    }

    public function getTargetAdministration(): ?string
    {
        return $this->targetAdministration;
    }

    public function setTargetAdministration(?string $targetAdministration): static
    {
        $this->targetAdministration = $targetAdministration;

        return $this;
    }

    public function getOtherAdministrations(): ?array
    {
        return $this->otherAdministrations;
    }

    public function setOtherAdministrations(?array $otherAdministrations): static
    {
        $this->otherAdministrations = $otherAdministrations;

        return $this;
    }

    public function getDiplomaFile(): ?string
    {
        return $this->diplomaFile;
    }

    public function setDiplomaFile(?string $diplomaFile): static
    {
        $this->diplomaFile = $diplomaFile;

        return $this;
    }
}
