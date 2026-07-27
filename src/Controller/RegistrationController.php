<?php

namespace App\Controller;

use App\Entity\IdentityDocument;
use App\Entity\Registrant;
use App\Form\Step1IdentityType;
use App\Service\DocumentUploadHandler;
use App\Service\UploadException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    private const string SESSION_KEY = 'registration.registrant';

    public function __construct(
        private readonly DocumentUploadHandler $uploadHandler,
    ) {
    }

    #[Route('/register/step1', name: 'register_step1', methods: ['GET', 'POST'])]
    public function step1(Request $request): Response
    {
        $identityDocument = new IdentityDocument();
        $registrant = new Registrant($identityDocument);

        $form = $this->createForm(Step1IdentityType::class, $registrant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->attachUploadedFile($form, 'passportFile', $identityDocument, 'setPassportFile', $registrant);
                $this->attachUploadedFile($form, 'residentCardFile', $identityDocument, 'setResidentCardFile', $registrant);
                $this->attachUploadedFile($form, 'consularCardFile', $identityDocument, 'setConsularCardFile', $registrant);
            } catch (UploadException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->render('registration/step1.html.twig', [
                    'form' => $form,
                    'documentStatuses' => $this->computeDocumentStatuses($identityDocument),
                ]);
            }

            $request->getSession()->set(self::SESSION_KEY, serialize($registrant));

            $this->addFlash('success', 'Votre identité a bien été enregistrée.');

            return $this->redirectToRoute('register_step2');
        }

        return $this->render('registration/step1.html.twig', [
            'form' => $form,
            'documentStatuses' => $this->computeDocumentStatuses($identityDocument),
        ]);
    }

    #[Route('/register/step2', name: 'register_step2', methods: ['GET'])]
    public function step2(Request $request): Response
    {
        $serialized = $request->getSession()->get(self::SESSION_KEY);

        if (!\is_string($serialized)) {
            $this->addFlash('warning', 'Merci de commencer par renseigner votre identité.');

            return $this->redirectToRoute('register_step1');
        }

        /** @var Registrant $registrant */
        $registrant = unserialize($serialized, ['allowed_classes' => [
            Registrant::class,
            IdentityDocument::class,
            \App\Entity\Enum\ProfileType::class,
            \App\Entity\Enum\RegistrantStatus::class,
            \Symfony\Component\Uid\Uuid::class,
            \Symfony\Component\Uid\UuidV7::class,
            \DateTimeImmutable::class,
        ]]);

        return $this->render('registration/step2.html.twig', [
            'registrant' => $registrant,
        ]);
    }

    private function attachUploadedFile(
        FormInterface $form,
        string $fieldName,
        IdentityDocument $identityDocument,
        string $setter,
        Registrant $registrant,
    ): void {
        $file = $form->get($fieldName)->getData();

        if ($file === null) {
            return;
        }

        $path = $this->uploadHandler->handle($file, $registrant->getId());
        $identityDocument->$setter($path);
    }

    /**
     * @return array<string, array{level: string, label: string}>
     */
    private function computeDocumentStatuses(IdentityDocument $identityDocument): array
    {
        return [
            'passport' => $this->computeStatus($identityDocument->getPassportExpiresAt()),
            'residentCard' => $this->computeStatus($identityDocument->getResidentCardExpiresAt()),
            'consularCard' => $this->computeStatus($identityDocument->getConsularCardExpiresAt()),
        ];
    }

    /**
     * @return array{level: string, label: string}
     */
    private function computeStatus(?\DateTimeImmutable $expiresAt): array
    {
        if ($expiresAt === null) {
            return ['level' => 'unknown', 'label' => 'À renseigner'];
        }

        $daysLeft = (int) (new \DateTimeImmutable('today'))->diff($expiresAt)->format('%r%a');

        if ($daysLeft < 0) {
            return ['level' => 'expired', 'label' => 'Expirée'];
        }

        if ($daysLeft <= 90) {
            return ['level' => 'warning', 'label' => \sprintf("En cours d'expiration (J-%d)", $daysLeft)];
        }

        return ['level' => 'valid', 'label' => 'En cours de validité'];
    }
}
