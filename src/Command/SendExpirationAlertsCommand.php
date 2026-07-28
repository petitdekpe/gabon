<?php

namespace App\Command;

use App\Entity\AlertLog;
use App\Entity\Registrant;
use App\Repository\AlertLogRepository;
use App\Repository\RegistrantRepository;
use App\Service\Alert\SendAlertInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Cron recommandé : 0 8 * * * php bin/console app:alerts:send
 */
#[AsCommand(
    name: 'app:alerts:send',
    description: "Envoie les alertes d'échéance documentaire (J-90 et J-30) par e-mail et SMS",
)]
class SendExpirationAlertsCommand extends Command
{
    private const array THRESHOLDS_DAYS = [90, 30];

    private const array DOCUMENT_LABELS = [
        'passport' => 'passeport',
        'residentCard' => 'carte de résident',
        'consularCard' => 'carte consulaire',
    ];

    public function __construct(
        private readonly RegistrantRepository $registrantRepository,
        private readonly AlertLogRepository $alertLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly SendAlertInterface $smsSender,
        #[Autowire('%env(MAILER_FROM_ADDRESS)%')]
        private readonly string $fromAddress,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $matches = $this->registrantRepository->findExpiringExactly(self::THRESHOLDS_DAYS);

        if ($matches === []) {
            $io->success('Aucun document n\'expire dans exactement 90 ou 30 jours aujourd\'hui.');

            return Command::SUCCESS;
        }

        $emailsSent = 0;
        $smsSent = 0;

        foreach ($matches as $match) {
            $registrant = $match['registrant'];
            $documentKey = $match['document'];
            $days = $match['days'];
            $documentLabel = self::DOCUMENT_LABELS[$documentKey];
            $expiresAt = $this->getExpiresAt($registrant, $documentKey);

            if ($expiresAt === null) {
                continue;
            }

            if (!$this->alertLogRepository->wasAlreadySentToday($registrant, $documentKey, 'email')) {
                $this->sendEmailAlert($registrant, $documentLabel, $expiresAt, $days);
                $this->entityManager->persist(new AlertLog($registrant, $documentKey, 'email'));
                ++$emailsSent;
            }

            if (!$this->alertLogRepository->wasAlreadySentToday($registrant, $documentKey, 'sms')) {
                $this->sendSmsAlert($registrant, $documentLabel, $days);
                $this->entityManager->persist(new AlertLog($registrant, $documentKey, 'sms'));
                ++$smsSent;
            }

            $io->writeln(\sprintf(
                ' · %s %s — %s (J-%d)',
                $registrant->getLastName(),
                $registrant->getFirstName(),
                $documentLabel,
                $days,
            ));
        }

        $this->entityManager->flush();

        $io->success(\sprintf('%d alerte(s) envoyée(s) : %d e-mail(s), %d SMS.', $emailsSent + $smsSent, $emailsSent, $smsSent));

        return Command::SUCCESS;
    }

    private function getExpiresAt(\App\Entity\Registrant $registrant, string $documentKey): ?\DateTimeImmutable
    {
        $document = $registrant->getIdentityDocument();

        return match ($documentKey) {
            'passport' => $document->getPassportExpiresAt(),
            'residentCard' => $document->getResidentCardExpiresAt(),
            'consularCard' => $document->getConsularCardExpiresAt(),
            default => null,
        };
    }

    private function sendEmailAlert(\App\Entity\Registrant $registrant, string $documentLabel, \DateTimeImmutable $expiresAt, int $days): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, 'Diaspora Gabonaise'))
            ->to($registrant->getEmail())
            ->subject(\sprintf('Votre %s expire dans %d jours', $documentLabel, $days))
            ->htmlTemplate('emails/expiration_alert.html.twig')
            ->context([
                'registrant' => $registrant,
                'documentLabel' => $documentLabel,
                'expiresAt' => $expiresAt,
                'days' => $days,
            ])
        ;

        $this->mailer->send($email);
    }

    private function sendSmsAlert(\App\Entity\Registrant $registrant, string $documentLabel, int $days): void
    {
        $message = \sprintf(
            'Diaspora Gabonaise : votre %s expire dans %d jours (réf. %s). Merci de préparer son renouvellement.',
            $documentLabel,
            $days,
            $registrant->getReference(),
        );

        $this->smsSender->send($registrant->getLocalPhone(), $message);
    }
}
