<?php

namespace App\Service;

use App\Entity\Registrant;

/**
 * Calcule le statut d'expiration (valide / à surveiller / expiré) d'un document
 * administratif, utilisé à la fois par le parcours d'inscription et le back-office.
 */
class DocumentStatusService
{
    public const int WARNING_THRESHOLD_DAYS = 90;

    public function getDaysRemaining(?\DateTimeImmutable $expiresAt): ?int
    {
        if ($expiresAt === null) {
            return null;
        }

        return (int) (new \DateTimeImmutable('today'))->diff($expiresAt)->format('%r%a');
    }

    /**
     * @return 'unknown'|'valid'|'warning'|'expired'
     */
    public function getStatus(?\DateTimeImmutable $expiresAt): string
    {
        $days = $this->getDaysRemaining($expiresAt);

        if ($days === null) {
            return 'unknown';
        }

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= self::WARNING_THRESHOLD_DAYS) {
            return 'warning';
        }

        return 'valid';
    }

    public function getStatusLabel(string $status, ?int $days = null): string
    {
        return match ($status) {
            'expired' => 'Expirée',
            'warning' => \sprintf("En cours d'expiration (J-%d)", $days ?? 0),
            'valid' => 'En cours de validité',
            default => 'À renseigner',
        };
    }

    /**
     * @return array{status: string, days: ?int, label: string}
     */
    public function getStatusInfo(?\DateTimeImmutable $expiresAt): array
    {
        $status = $this->getStatus($expiresAt);
        $days = $this->getDaysRemaining($expiresAt);

        return [
            'status' => $status,
            'days' => $days,
            'label' => $this->getStatusLabel($status, $days),
        ];
    }

    /**
     * @return array<string, array{status: string, days: ?int, label: string}>
     */
    public function getStatusForRegistrant(Registrant $registrant): array
    {
        $document = $registrant->getIdentityDocument();

        return [
            'passport' => $this->getStatusInfo($document->getPassportExpiresAt()),
            'residentCard' => $this->getStatusInfo($document->getResidentCardExpiresAt()),
            'consularCard' => $this->getStatusInfo($document->getConsularCardExpiresAt()),
        ];
    }

    public function getStatusBadgeHtml(?\DateTimeImmutable $expiresAt): string
    {
        $info = $this->getStatusInfo($expiresAt);

        return \sprintf(
            '<span class="gab-badge gab-badge--%s">%s</span>',
            htmlspecialchars($info['status'], \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($info['label'], \ENT_QUOTES, 'UTF-8'),
        );
    }
}
