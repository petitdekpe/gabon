<?php

namespace App\Entity;

use App\Repository\SiteImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Image remplaçable depuis le back-office pour un emplacement donné du site
 * public (voir App\Config\SiteImageCatalog pour la liste des emplacements).
 * Tant qu'aucune ligne n'existe pour un slot, le site affiche l'image par
 * défaut livrée avec le code (public/img/...).
 */
#[ORM\Entity(repositoryClass: SiteImageRepository::class)]
#[ORM\Table(options: ['engine' => 'InnoDB'])]
#[ORM\UniqueConstraint(columns: ['slot'])]
class SiteImage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $slot;

    #[ORM\Column(length: 150)]
    private string $filename;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalFilename = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdminUser $updatedBy = null;

    public function __construct(string $slot)
    {
        $this->id = Uuid::v7();
        $this->slot = $slot;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedBy(): ?AdminUser
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?AdminUser $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
