<?php

namespace App\Config;

/**
 * Registre des emplacements d'images du site public modifiables depuis le
 * back-office. Chaque emplacement porte une image par défaut, livrée avec le
 * code dans public/img/, affichée tant qu'aucun administrateur ne l'a remplacée.
 */
final class SiteImageCatalog
{
    /**
     * @var array<string, array{label: string, default: string}>
     */
    private const array SLOTS = [
        'home_hero_1' => [
            'label' => 'Accueil — bandeau principal, photo 1/3',
            'default' => '/img/parc-national-lope-aerien.jpg',
        ],
        'home_hero_2' => [
            'label' => 'Accueil — bandeau principal, photo 2/3',
            'default' => '/img/countries-gabon.jpg',
        ],
        'home_hero_3' => [
            'label' => 'Accueil — bandeau principal, photo 3/3',
            'default' => '/img/gabon-image1.jpg',
        ],
        'home_showcase_1' => [
            'label' => 'Accueil — vitrine « Gabon Développement »',
            'default' => '/img/Oligui-travaux.webp',
        ],
        'home_showcase_2' => [
            'label' => 'Accueil — vitrine « Parc de la Lopé »',
            'default' => '/img/elephants-gabon-1200x800.jpg',
        ],
        'home_showcase_3' => [
            'label' => 'Accueil — vitrine « Rivières & forêts »',
            'default' => '/img/riviere-pirogue-gabon.jpg',
        ],
    ];

    public static function exists(string $slot): bool
    {
        return isset(self::SLOTS[$slot]);
    }

    public static function getLabel(string $slot): string
    {
        return self::SLOTS[$slot]['label'] ?? $slot;
    }

    public static function getDefaultPath(string $slot): string
    {
        return self::SLOTS[$slot]['default'] ?? '';
    }

    /**
     * @return array<string, array{label: string, default: string}>
     */
    public static function all(): array
    {
        return self::SLOTS;
    }
}
