<?php

namespace App\Twig;

use App\Security\CspNonceGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CspNonceExtension extends AbstractExtension
{
    public function __construct(
        private readonly CspNonceGenerator $cspNonceGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->cspNonceGenerator->getNonce(...)),
        ];
    }
}
