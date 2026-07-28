<?php

namespace App\Service\Alert;

/**
 * Abstraction du canal SMS, pour pouvoir brancher un vrai fournisseur
 * (Twilio, Vonage, un agrégateur local...) sans toucher au reste de l'application.
 */
interface SendAlertInterface
{
    public function send(string $phoneNumber, string $message): bool;
}
