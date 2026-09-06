<?php

namespace App\Security;

/**
 * Un seul nonce CSP par requête, partagé entre le twig `csp_nonce()` (posé sur
 * les <script> inline) et SecurityHeadersListener (qui l'ajoute à l'en-tête
 * Content-Security-Policy) — généré une fois puis mis en cache le temps de
 * la requête pour que les deux lectures renvoient la même valeur.
 */
class CspNonceGenerator
{
    private ?string $nonce = null;

    public function getNonce(): string
    {
        return $this->nonce ??= bin2hex(random_bytes(16));
    }
}
