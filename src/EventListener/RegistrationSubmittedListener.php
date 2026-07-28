<?php

namespace App\EventListener;

use App\Config\DeploymentConfig;
use App\Event\RegistrationSubmittedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * À la soumission réussie d'un dossier : e-mail de confirmation à l'inscrit
 * (référence + récapitulatif) et notification au Bureau Exécutif de l'AGB.
 */
#[AsEventListener(event: RegistrationSubmittedEvent::class)]
class RegistrationSubmittedListener
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly DeploymentConfig $deploymentConfig,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM_ADDRESS)%')]
        private readonly string $fromAddress,
    ) {
    }

    public function __invoke(RegistrationSubmittedEvent $event): void
    {
        $registrant = $event->getRegistrant();
        $from = new Address($this->fromAddress, 'Diaspora Gabonaise');

        try {
            $confirmation = (new TemplatedEmail())
                ->from($from)
                ->to($registrant->getEmail())
                ->subject(\sprintf('Votre dossier %s a bien été enregistré', $registrant->getReference()))
                ->htmlTemplate('emails/registration_confirmation.html.twig')
                ->context(['registrant' => $registrant])
            ;
            $this->mailer->send($confirmation);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Échec d\'envoi de l\'e-mail de confirmation à l\'inscrit.', [
                'registrant_id' => (string) $registrant->getId(),
                'reference' => $registrant->getReference(),
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            $notification = (new TemplatedEmail())
                ->from($from)
                ->to($this->deploymentConfig->getAdminNotifyEmail())
                ->subject(\sprintf('Nouvelle inscription : %s (%s)', $registrant->getReference(), $this->deploymentConfig->getLabel()))
                ->htmlTemplate('emails/admin_notification.html.twig')
                ->context(['registrant' => $registrant, 'deployment' => $this->deploymentConfig])
            ;
            $this->mailer->send($notification);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Échec d\'envoi de la notification au Bureau Exécutif de l\'AGB.', [
                'registrant_id' => (string) $registrant->getId(),
                'reference' => $registrant->getReference(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
