<?php

namespace App\Config;

use App\Entity\Enum\AfricanCountry;
use Symfony\Component\Intl\Countries;

/**
 * Point d'accès unique à la liste des pays de la Zone Afrique pour l'architecture
 * multi-pays (chaque déploiement pilote peut cibler un pays différent).
 *
 * S'appuie sur l'énumération {@see AfricanCountry} (source de vérité des codes ISO)
 * pour rester cohérent avec les contraintes de validation déjà posées sur Registrant.
 */
final class ZoneAfriqueCountries
{
    /**
     * @return list<string> codes ISO 3166-1 alpha-2
     */
    public static function codes(): array
    {
        return AfricanCountry::codes();
    }

    /**
     * @return array<string, string> libellé français => code ISO
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (AfricanCountry::cases() as $country) {
            $choices[Countries::getName($country->value, 'fr')] = $country->value;
        }
        ksort($choices);

        return $choices;
    }

    public static function isValid(string $code): bool
    {
        return AfricanCountry::tryFrom(strtoupper($code)) !== null;
    }

    public static function label(string $code): string
    {
        return Countries::getName(strtoupper($code), 'fr');
    }
}
