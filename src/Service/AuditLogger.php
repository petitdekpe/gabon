<?php

namespace App\Service;

use App\Entity\AdminUser;
use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Journalise les actions sensibles du back-office administrateur dans AuditLog
 * (connexion, consultation de fiche, suppression, export, modification de droits...).
 */
class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function log(string $action, ?object $entity = null, ?AdminUser $adminUser = null, ?array $payload = null): void
    {
        $log = new AuditLog($action);
        $log->setAdminUser($adminUser);
        $log->setPayload($payload);

        if ($entity !== null) {
            $log->setEntityType((new \ReflectionClass($entity))->getShortName());

            if (method_exists($entity, 'getId')) {
                $log->setEntityId((string) $entity->getId());
            }
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request !== null) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent(substr((string) $request->headers->get('User-Agent'), 0, 255));
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
