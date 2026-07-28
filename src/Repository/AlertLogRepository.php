<?php

namespace App\Repository;

use App\Entity\AlertLog;
use App\Entity\Registrant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlertLog>
 */
class AlertLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertLog::class);
    }

    /**
     * Empêche de renvoyer deux fois la même alerte (même inscrit, même document,
     * même seuil de jours) au sein d'une fenêtre de 24h.
     */
    public function wasAlreadySentToday(Registrant $registrant, string $document, string $channel): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.registrant = :registrantId')
            ->andWhere('a.document = :document')
            ->andWhere('a.channel = :channel')
            ->andWhere('a.sentAt >= :since')
            // Lier l'objet Registrant directement mappe mal le type DBAL personnalisé
            // "uuid" pour cette comparaison (le filtre ne trouvait alors jamais de
            // correspondance) : on lie explicitement l'identifiant avec son type.
            ->setParameter('registrantId', $registrant->getId(), 'uuid')
            ->setParameter('document', $document)
            ->setParameter('channel', $channel)
            ->setParameter('since', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
