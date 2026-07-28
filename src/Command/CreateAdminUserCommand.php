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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:admin:create',
    description: 'Crée (ou met à jour) un compte administrateur du back-office',
)]
class CreateAdminUserCommand extends Command
{
    private const array ROLE_CHOICES = ['ADMIN' => 'ROLE_ADMIN', 'ADMIN_READER' => 'ROLE_ADMIN_READER'];

    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly AuditLogger $auditLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, "Adresse e-mail du compte (invite si absente)")
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Mot de passe (généré aléatoirement et affiché si absent)')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'ADMIN (accès complet) ou ADMIN_READER (consultation)', 'ADMIN')
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Crée le compte désactivé')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Si le compte existe déjà, met à jour son mot de passe/rôle au lieu d\'échouer')
            ->setHelp(<<<'HELP'
                Crée un compte administrateur pour le back-office (/admin).

                  <info>php %command.full_name% admin@example.com</info>
                  <info>php %command.full_name% admin@example.com --password=SecretPass123! --role=ADMIN_READER</info>

                Sans <info>--password</info>, un mot de passe aléatoire est généré et affiché une seule fois :
                notez-le immédiatement, il n'est pas stocké en clair.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Adresse e-mail du compte administrateur');
        }
        $email = trim((string) $email);

        $roleKey = strtoupper((string) $input->getOption('role'));
        if (!isset(self::ROLE_CHOICES[$roleKey])) {
            $io->error(\sprintf('Rôle inconnu "%s". Valeurs possibles : %s.', $roleKey, implode(', ', array_keys(self::ROLE_CHOICES))));

            return Command::FAILURE;
        }
        $role = self::ROLE_CHOICES[$roleKey];

        $existing = $this->adminUserRepository->findOneBy(['email' => $email]);

        if ($existing !== null && !$input->getOption('update')) {
            $io->error(\sprintf(
                'Un compte existe déjà pour "%s". Relancez avec --update pour changer son mot de passe/rôle.',
                $email,
            ));

            return Command::FAILURE;
        }

        $generatedPassword = null;
        $password = $input->getOption('password');
        if (!$password) {
            $generatedPassword = $password = bin2hex(random_bytes(8));
        }

        $adminUser = $existing ?? new AdminUser();
        $adminUser->setEmail($email);
        $adminUser->setRoles([$role]);
        $adminUser->setActive(!$input->getOption('inactive'));
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, $password));

        $violations = $this->validator->validate($adminUser);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error($violation->getPropertyPath().' : '.$violation->getMessage());
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->auditLogger->log(
            $existing !== null ? 'admin.update' : 'admin.create',
            $adminUser,
            null,
            ['email' => $email, 'role' => $role, 'source' => 'cli'],
        );

        $io->success(\sprintf(
            '%s le compte "%s" (%s%s).',
            $existing !== null ? 'Mis à jour' : 'Créé',
            $email,
            $role,
            $input->getOption('inactive') ? ', désactivé' : '',
        ));

        if ($generatedPassword !== null) {
            $io->warning('Mot de passe généré (à noter, il ne sera plus affiché) :');
            $io->writeln('  '.$generatedPassword);
        }

        return Command::SUCCESS;
    }
}
