<?php

namespace App\EventListener;

use App\Entity\AdminUser;
use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Journalise chaque connexion réussie au back-office administrateur.
 * Ne se déclenche que pour le firewall "admin" (provider AdminUser) : les
 * connexions sur d'autres firewalls éventuels ne sont pas concernées.
 */
#[AsEventListener(event: LoginSuccessEvent::class)]
class AdminLoginAuditListener
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof AdminUser) {
            return;
        }

        $this->auditLogger->log('admin.login', $user, $user);
    }
}
