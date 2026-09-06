<?php

namespace App\Repository;

use App\Entity\SiteImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteImage>
 */
class SiteImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteImage::class);
    }

    public function findBySlot(string $slot): ?SiteImage
    {
        return $this->findOneBy(['slot' => $slot]);
    }

    /**
     * @return array<string, SiteImage>
     */
    public function findAllIndexedBySlot(): array
    {
        $bySlot = [];

        foreach ($this->findAll() as $siteImage) {
            $bySlot[$siteImage->getSlot()] = $siteImage;
        }

        return $bySlot;
    }
}
