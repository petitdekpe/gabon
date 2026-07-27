<?php

namespace App\Event;

use App\Entity\Registrant;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Émis lorsqu'un dossier de recensement est soumis avec succès (statut 'submitted').
 * Consommé par les abonnés de notification (courriel, SMS, etc.).
 */
final class RegistrationSubmittedEvent extends Event
{
    public function __construct(
        private readonly Registrant $registrant,
    ) {
    }

    public function getRegistrant(): Registrant
    {
        return $this->registrant;
    }
}
