<?php

namespace App\DataFixtures;

use App\Entity\AdminUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Compte super-administrateur par défaut pour la phase pilote.
 *
 * ⚠️ Mot de passe de démarrage à changer immédiatement en production.
 */
class AdminUserFixtures extends Fixture
{
    public const string DEFAULT_ADMIN_EMAIL = 'admin@diaspora-gabonaise.ga';
    public const string DEFAULT_ADMIN_PASSWORD = 'ChangeMe123!';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new AdminUser();
        $admin->setEmail(self::DEFAULT_ADMIN_EMAIL);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setActive(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::DEFAULT_ADMIN_PASSWORD));

        $manager->persist($admin);
        $manager->flush();
    }
}
