<?php

namespace App\Command;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:delete',
    description: 'Supprime un compte administrateur du back-office',
)]
class DeleteAdminUserCommand extends Command
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, "Adresse e-mail du compte à supprimer (invite si absente)")
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ne pas demander de confirmation')
            ->setHelp(<<<'HELP'
                Supprime définitivement un compte administrateur du back-office.

                  <info>php %command.full_name% admin@example.com</info>
                  <info>php %command.full_name% admin@example.com --force</info>

                Refuse de supprimer le dernier compte ROLE_ADMIN actif restant, pour ne pas
                verrouiller l'accès au back-office.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Adresse e-mail du compte à supprimer');
        }
        $email = trim((string) $email);

        $adminUser = $this->adminUserRepository->findOneBy(['email' => $email]);

        if ($adminUser === null) {
            $io->error(\sprintf('Aucun compte administrateur trouvé pour "%s".', $email));

            return Command::FAILURE;
        }

        if ($this->isLastActiveAdmin($adminUser)) {
            $io->error("Impossible de supprimer ce compte : c'est le dernier compte ROLE_ADMIN actif. Créez-en un autre d'abord (app:admin:create).");

            return Command::FAILURE;
        }

        if (!$input->getOption('force')) {
            $confirmed = $io->confirm(\sprintf(
                'Supprimer définitivement le compte "%s" (%s) ?',
                $email,
                implode(', ', $adminUser->getRoles()),
            ), false);

            if (!$confirmed) {
                $io->comment('Suppression annulée.');

                return Command::SUCCESS;
            }
        }

        $this->auditLogger->log('admin.delete', $adminUser, null, ['email' => $email, 'source' => 'cli']);

        $this->entityManager->remove($adminUser);
        $this->entityManager->flush();

        $io->success(\sprintf('Le compte "%s" a été supprimé.', $email));

        return Command::SUCCESS;
    }

    private function isLastActiveAdmin(AdminUser $candidate): bool
    {
        if (!\in_array('ROLE_ADMIN', $candidate->getRoles(), true) || !$candidate->isActive()) {
            return false;
        }

        // Les rôles étant stockés en JSON, on ne peut pas filtrer ROLE_ADMIN en DQL :
        // on charge les autres comptes actifs et on vérifie leurs rôles en PHP.
        $otherAdmins = $this->adminUserRepository->createQueryBuilder('a')
            ->andWhere('a.id != :id')
            ->andWhere('a.active = true')
            ->setParameter('id', $candidate->getId(), 'uuid')
            ->getQuery()
            ->getResult();

        foreach ($otherAdmins as $other) {
            if (\in_array('ROLE_ADMIN', $other->getRoles(), true)) {
                return false;
            }
        }

        return true;
    }
}
