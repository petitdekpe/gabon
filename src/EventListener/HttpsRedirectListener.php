<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Force HTTPS en production. N'a aucun effet en dev/test pour ne pas gêner
 * le serveur PHP intégré, qui ne sert que du HTTP en local.
 */
#[AsEventListener(event: 'kernel.request', priority: 100)]
class HttpsRedirectListener
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || 'prod' !== $this->environment) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isSecure()) {
            return;
        }

        $url = 'https://'.$request->getHost().$request->getRequestUri();
        $event->setResponse(new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY));
    }
}
