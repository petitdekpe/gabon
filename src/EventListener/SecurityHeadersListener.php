<?php

namespace App\EventListener;

use App\Security\CspNonceGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * En-têtes de sécurité appliqués à toutes les réponses. La CSP n'est activée
 * qu'en dehors du mode debug : le web debug toolbar et le rechargement à chaud
 * de FrankenPHP utilisent des scripts inline incompatibles avec une CSP stricte.
 */
#[AsEventListener(event: 'kernel.response')]
class SecurityHeadersListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        private readonly CspNonceGenerator $cspNonceGenerator,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        if (!$this->debug) {
            $nonce = $this->cspNonceGenerator->getNonce();

            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' https://ga.jspm.io",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data:",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }
    }
}
