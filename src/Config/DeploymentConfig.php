<?php

namespace App\Config;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Paramètres du déploiement pilote courant. Chaque pays de la Zone Afrique
 * peut avoir sa propre instance de la plateforme, configurée uniquement via
 * ces trois variables d'environnement — aucun code à changer pour déployer
 * ailleurs qu'au Bénin.
 */
final class DeploymentConfig
{
    public function __construct(
        #[Autowire('%env(DEPLOYMENT_COUNTRY)%')]
        private readonly string $country,
        #[Autowire('%env(DEPLOYMENT_LABEL)%')]
        private readonly string $label,
        #[Autowire('%env(ADMIN_NOTIFY_EMAIL)%')]
        private readonly string $adminNotifyEmail,
    ) {
    }

    /**
     * Code ISO 3166-1 alpha-2 du pays de déploiement (ex. 'BJ').
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Libellé humain du déploiement (ex. "Bénin (Cotonou)").
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Adresse e-mail du Bureau Exécutif de l'AGB recevant les notifications.
     */
    public function getAdminNotifyEmail(): string
    {
        return $this->adminNotifyEmail;
    }
}
