<?php

namespace App\Service\Alert;

use Psr\Log\LoggerInterface;

/**
 * Implémentation de secours de SendAlertInterface : consigne le SMS dans les
 * journaux au lieu de l'envoyer réellement.
 *
 * ⚠️ Aucun fournisseur SMS (Twilio, Vonage, agrégateur local...) n'est configuré
 * pour la phase pilote. Remplacer ce service par une implémentation réelle avant
 * la mise en production (créer une classe implémentant SendAlertInterface et
 * l'aliaser dans config/services.yaml à la place de celle-ci).
 */
class LoggerSmsSender implements SendAlertInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(string $phoneNumber, string $message): bool
    {
        $this->logger->info('SMS (simulation, aucun fournisseur configuré) : {message}', [
            'to' => $phoneNumber,
            'message' => $message,
        ]);

        return true;
    }
}
