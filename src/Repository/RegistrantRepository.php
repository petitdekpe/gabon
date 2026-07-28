<?php

namespace App\Repository;

use App\Entity\Enum\ProfileType;
use App\Entity\Enum\RegistrantStatus;
use App\Entity\Registrant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registrant>
 */
class RegistrantRepository extends ServiceEntityRepository
{
    public const int PER_PAGE = 25;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registrant::class);
    }

    /**
     * @param array{country?: string, profileType?: string, passportStatus?: string, consularStatus?: string, dateFrom?: string, dateTo?: string, search?: string} $filters
     *
     * @return Paginator<Registrant>
     */
    public function findFiltered(array $filters, int $page = 1): Paginator
    {
        $qb = $this->baseFilteredQueryBuilder($filters)
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(max(0, $page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
        ;

        return new Paginator($qb, fetchJoinCollection: false);
    }

    /**
     * @param array{country?: string, profileType?: string, passportStatus?: string, consularStatus?: string, dateFrom?: string, dateTo?: string, search?: string} $filters
     */
    private function baseFilteredQueryBuilder(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.identityDocument', 'doc')
            ->addSelect('doc')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
        ;

        if (!empty($filters['country'])) {
            $qb->andWhere('r.country = :country')->setParameter('country', $filters['country']);
        }

        if (!empty($filters['profileType'])) {
            $qb->andWhere('r.profileType = :profileType')->setParameter('profileType', ProfileType::from($filters['profileType']));
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('(r.lastName LIKE :search OR r.firstName LIKE :search OR r.reference LIKE :search)')
                ->setParameter('search', '%'.$filters['search'].'%');
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('r.createdAt >= :dateFrom')->setParameter('dateFrom', new \DateTimeImmutable($filters['dateFrom']));
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('r.createdAt <= :dateTo')->setParameter('dateTo', new \DateTimeImmutable($filters['dateTo'].' 23:59:59'));
        }

        if (!empty($filters['passportStatus'])) {
            $this->applyDocumentStatusFilter($qb, 'doc.passportExpiresAt', $filters['passportStatus'], 'passport');
        }

        if (!empty($filters['consularStatus'])) {
            $this->applyDocumentStatusFilter($qb, 'doc.consularCardExpiresAt', $filters['consularStatus'], 'consular');
        }

        return $qb;
    }

    private function applyDocumentStatusFilter(QueryBuilder $qb, string $field, string $status, string $paramPrefix): void
    {
        $today = new \DateTimeImmutable('today');
        $warningLimit = $today->modify('+90 days');

        match ($status) {
            'expired' => $qb->andWhere("{$field} < :{$paramPrefix}Today")
                ->setParameter("{$paramPrefix}Today", $today),
            'warning' => $qb->andWhere("{$field} >= :{$paramPrefix}Today AND {$field} <= :{$paramPrefix}Limit")
                ->setParameter("{$paramPrefix}Today", $today)
                ->setParameter("{$paramPrefix}Limit", $warningLimit),
            'valid' => $qb->andWhere("{$field} > :{$paramPrefix}Limit")
                ->setParameter("{$paramPrefix}Limit", $warningLimit),
            default => null,
        };
    }

    public function countSubmitted(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int> clé = valeur de ProfileType, valeur = effectif
     */
    public function countByProfileType(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.profileType AS profileType', 'COUNT(r.id) AS total')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
            ->groupBy('r.profileType')
            ->getQuery()
            ->getResult();

        $counts = ['student' => 0, 'employee' => 0, 'entrepreneur' => 0];
        foreach ($rows as $row) {
            $profileType = $row['profileType'];
            $key = $profileType instanceof ProfileType ? $profileType->value : (string) $profileType;
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }

    public function countExpiringWithinDays(int $days = 30): int
    {
        $today = new \DateTimeImmutable('today');
        $limit = $today->modify(\sprintf('+%d days', $days));

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)')
            ->innerJoin('r.identityDocument', 'doc')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
            ->andWhere('
                (doc.passportExpiresAt BETWEEN :today AND :limit) OR
                (doc.residentCardExpiresAt BETWEEN :today AND :limit) OR
                (doc.consularCardExpiresAt BETWEEN :today AND :limit)
            ')
            ->setParameter('today', $today)
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSubmittedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
            ->andWhere('r.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array{country?: string, profileType?: string, passportStatus?: string, consularStatus?: string, dateFrom?: string, dateTo?: string, search?: string} $filters
     *
     * @return iterable<Registrant>
     */
    public function findFilteredForExport(array $filters): iterable
    {
        return $this->baseFilteredQueryBuilder($filters)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->toIterable()
        ;
    }

    /**
     * Inscrits ayant un document expirant très exactement dans l'un des seuils
     * fournis (ex. J-90 ou J-30), pour l'envoi ciblé des alertes d'échéance.
     *
     * @param list<int> $daysThresholds
     *
     * @return list<array{registrant: Registrant, document: string, days: int}>
     */
    public function findExpiringExactly(array $daysThresholds): array
    {
        $documentFields = [
            'passport' => 'passportExpiresAt',
            'residentCard' => 'residentCardExpiresAt',
            'consularCard' => 'consularCardExpiresAt',
        ];

        $matches = [];

        foreach ($daysThresholds as $days) {
            $targetDate = (new \DateTimeImmutable('today'))->modify(\sprintf('+%d days', $days));

            foreach ($documentFields as $documentKey => $field) {
                $registrants = $this->createQueryBuilder('r')
                    ->innerJoin('r.identityDocument', 'doc')
                    ->andWhere('r.status = :status')
                    ->setParameter('status', RegistrantStatus::Submitted)
                    ->andWhere("doc.{$field} = :targetDate")
                    ->setParameter('targetDate', $targetDate)
                    ->getQuery()
                    ->getResult();

                foreach ($registrants as $registrant) {
                    $matches[] = ['registrant' => $registrant, 'document' => $documentKey, 'days' => $days];
                }
            }
        }

        return $matches;
    }

    /**
     * @return list<Registrant>
     */
    public function findMostRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', RegistrantStatus::Submitted)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
