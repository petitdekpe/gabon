<?php

namespace App\Tests\Functional;

use App\Service\DocumentStatusService;
use PHPUnit\Framework\TestCase;

/**
 * Unitaire (aucun kernel requis) : le service ne dépend que de l'horloge système.
 */
class DocumentStatusServiceTest extends TestCase
{
    private DocumentStatusService $service;

    protected function setUp(): void
    {
        $this->service = new DocumentStatusService();
    }

    public function testDocumentFarFromExpiryIsValid(): void
    {
        $expiresAt = (new \DateTimeImmutable('today'))->modify('+200 days');

        self::assertSame('valid', $this->service->getStatus($expiresAt));
    }

    public function testDocumentWithinNinetyDaysIsWarning(): void
    {
        $expiresAt = (new \DateTimeImmutable('today'))->modify('+45 days');

        self::assertSame('warning', $this->service->getStatus($expiresAt));
        self::assertSame(45, $this->service->getDaysRemaining($expiresAt));
    }

    public function testPastDocumentIsExpired(): void
    {
        $expiresAt = (new \DateTimeImmutable('today'))->modify('-10 days');

        self::assertSame('expired', $this->service->getStatus($expiresAt));
        self::assertSame('Expirée', $this->service->getStatusLabel('expired'));
    }

    public function testNullDateIsUnknown(): void
    {
        self::assertSame('unknown', $this->service->getStatus(null));
        self::assertNull($this->service->getDaysRemaining(null));
    }
}
