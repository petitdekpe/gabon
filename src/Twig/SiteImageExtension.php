<?php

namespace App\Twig;

use App\Config\SiteImageCatalog;
use App\Repository\SiteImageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Résout l'URL affichée pour un emplacement d'image du site public : celle
 * téléversée depuis le back-office si un administrateur l'a remplacée, sinon
 * l'image par défaut du dépôt (voir App\Config\SiteImageCatalog).
 */
class SiteImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly SiteImageRepository $siteImageRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_image', $this->resolve(...)),
        ];
    }

    public function resolve(string $slot): string
    {
        $siteImage = $this->siteImageRepository->findBySlot($slot);

        if ($siteImage !== null) {
            return '/uploads/site-images/'.$siteImage->getFilename();
        }

        return SiteImageCatalog::getDefaultPath($slot);
    }
}
