<?php

namespace App\Controller;

use App\Config\DeploymentConfig;
use App\Entity\EmployeeProfile;
use App\Entity\EntrepreneurProfile;
use App\Entity\Enum\ProfileType;
use App\Entity\Enum\RegistrantStatus;
use App\Entity\IdentityDocument;
use App\Entity\Registrant;
use App\Entity\StudentProfile;
use App\Event\RegistrationSubmittedEvent;
use App\Form\Step1IdentityType;
use App\Form\Step2EmployeeType;
use App\Form\Step2EntrepreneurType;
use App\Form\Step2StudentType;
use App\Service\DocumentStatusService;
use App\Service\DocumentUploadHandler;
use App\Service\UploadException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    private const string SESSION_KEY = 'registration.registrant';

    public function __construct(
        private readonly DocumentUploadHandler $uploadHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ValidatorInterface $validator,
        private readonly DocumentStatusService $documentStatusService,
        private readonly DeploymentConfig $deploymentConfig,
        private readonly RateLimiterFactory $registrationStep2Limiter,
    ) {
    }

    #[Route('/register/step1', name: 'register_step1', methods: ['GET', 'POST'])]
    public function step1(Request $request): Response
    {
        $identityDocument = new IdentityDocument();
        $registrant = new Registrant($identityDocument);
        // Pré-sélectionne le pays du déploiement pilote sans verrouiller le champ :
        // l'utilisateur peut toujours choisir un autre pays de la Zone Afrique.
        $registrant->setCountry($this->deploymentConfig->getCountry());

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
                    'documentStatuses' => $this->documentStatusService->getStatusForRegistrant($registrant),
                ]);
            }

            $request->getSession()->set(self::SESSION_KEY, serialize($registrant));

            $this->addFlash('success', 'Votre identité a bien été enregistrée.');

            return $this->redirectToRoute('register_step2');
        }

        return $this->render('registration/step1.html.twig', [
            'form' => $form,
            'documentStatuses' => $this->documentStatusService->getStatusForRegistrant($registrant),
        ]);
    }

    #[Route('/register/profile-type', name: 'register_profile_type', methods: ['POST'])]
    public function profileType(Request $request): JsonResponse
    {
        $registrant = $this->getSessionRegistrant($request);

        if ($registrant === null) {
            return $this->json(['error' => 'no_session'], Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $profileType = ProfileType::tryFrom((string) ($payload['profileType'] ?? ''));

        if ($profileType === null) {
            return $this->json(['error' => 'invalid_profile_type'], Response::HTTP_BAD_REQUEST);
        }

        $registrant->setProfileType($profileType);
        $request->getSession()->set(self::SESSION_KEY, serialize($registrant));

        return $this->json(['status' => 'ok', 'profileType' => $profileType->value]);
    }

    #[Route('/register/step2', name: 'register_step2', methods: ['GET', 'POST'])]
    public function step2(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $limiter = $this->registrationStep2Limiter->create($request->getClientIp());

            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException(null, 'Trop de tentatives de soumission depuis cette adresse. Merci de réessayer plus tard.');
            }
        }

        $registrant = $this->getSessionRegistrant($request);

        if ($registrant === null) {
            $this->addFlash('warning', 'Merci de commencer par renseigner votre identité.');

            return $this->redirectToRoute('register_step1');
        }

        $studentProfile = (new StudentProfile())->setOtherAdministrations([]);
        $employeeProfile = (new EmployeeProfile())->setOtherCompanies([]);
        $entrepreneurProfile = (new EntrepreneurProfile())->setOtherTargets([]);

        $studentForm = $this->createForm(Step2StudentType::class, $studentProfile);
        $employeeForm = $this->createForm(Step2EmployeeType::class, $employeeProfile);
        $entrepreneurForm = $this->createForm(Step2EntrepreneurType::class, $entrepreneurProfile);

        $studentForm->handleRequest($request);
        $employeeForm->handleRequest($request);
        $entrepreneurForm->handleRequest($request);

        /** @var array{form: FormInterface, type: ProfileType, profile: StudentProfile|EmployeeProfile|EntrepreneurProfile, fileField: string, setter: string}|null $submission */
        $submission = match (true) {
            $studentForm->isSubmitted() => [
                'form' => $studentForm,
                'type' => ProfileType::Student,
                'profile' => $studentProfile,
                'fileField' => 'diplomaFile',
                'setter' => 'setDiplomaFile',
            ],
            $employeeForm->isSubmitted() => [
                'form' => $employeeForm,
                'type' => ProfileType::Employee,
                'profile' => $employeeProfile,
                'fileField' => 'cvFile',
                'setter' => 'setCvFile',
            ],
            $entrepreneurForm->isSubmitted() => [
                'form' => $entrepreneurForm,
                'type' => ProfileType::Entrepreneur,
                'profile' => $entrepreneurProfile,
                'fileField' => 'businessPlanFile',
                'setter' => 'setBusinessPlanFile',
            ],
            default => null,
        };

        if ($submission !== null) {
            $consentGiven = $request->request->getBoolean('consentGiven');
            $registrant->setProfileType($submission['type']);
            $registrant->setConsentGiven($consentGiven);

            match ($submission['type']) {
                ProfileType::Student => $registrant->setStudentProfile($submission['profile']),
                ProfileType::Employee => $registrant->setEmployeeProfile($submission['profile']),
                ProfileType::Entrepreneur => $registrant->setEntrepreneurProfile($submission['profile']),
            };

            if ($submission['form']->isValid()) {
                try {
                    $this->attachUploadedFile($submission['form'], $submission['fileField'], $submission['profile'], $submission['setter'], $registrant);
                } catch (UploadException $exception) {
                    $this->addFlash('error', $exception->getMessage());

                    return $this->renderStep2($registrant, $studentForm, $employeeForm, $entrepreneurForm);
                }

                $violations = $this->validator->validate($registrant, null, ['Default', 'step2_completion']);

                if (\count($violations) === 0) {
                    $registrant->setStatus(RegistrantStatus::Submitted);
                    $registrant->setReference($this->generateReference());

                    $this->entityManager->persist($registrant);
                    $this->entityManager->flush();

                    $request->getSession()->remove(self::SESSION_KEY);

                    $this->eventDispatcher->dispatch(new RegistrationSubmittedEvent($registrant));

                    return $this->redirectToRoute('register_confirmation', ['reference' => $registrant->getReference()]);
                }

                $this->addFlash('error', 'Il manque des informations obligatoires pour finaliser votre dossier (dont le consentement RGPD).');
            } else {
                $this->addFlash('error', 'Merci de corriger les champs signalés avant de soumettre votre dossier.');
            }
        }

        return $this->renderStep2($registrant, $studentForm, $employeeForm, $entrepreneurForm);
    }

    #[Route('/register/confirmation/{reference}', name: 'register_confirmation', methods: ['GET'])]
    public function confirmation(string $reference): Response
    {
        $registrant = $this->entityManager->getRepository(Registrant::class)->findOneBy(['reference' => $reference]);

        if ($registrant === null) {
            throw $this->createNotFoundException('Dossier introuvable pour cette référence.');
        }

        return $this->render('registration/confirmation.html.twig', [
            'registrant' => $registrant,
        ]);
    }

    private function renderStep2(
        Registrant $registrant,
        FormInterface $studentForm,
        FormInterface $employeeForm,
        FormInterface $entrepreneurForm,
    ): Response {
        return $this->render('registration/step2.html.twig', [
            'registrant' => $registrant,
            'initialProfileType' => $registrant->getProfileType()?->value,
            'studentForm' => $studentForm,
            'employeeForm' => $employeeForm,
            'entrepreneurForm' => $entrepreneurForm,
        ]);
    }

    private function getSessionRegistrant(Request $request): ?Registrant
    {
        $serialized = $request->getSession()->get(self::SESSION_KEY);

        if (!\is_string($serialized)) {
            return null;
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

        return $registrant;
    }

    private function generateReference(): string
    {
        $repository = $this->entityManager->getRepository(Registrant::class);

        do {
            $reference = \sprintf('DG-BJ-%s-%05d', date('Y'), random_int(0, 99999));
        } while ($repository->findOneBy(['reference' => $reference]) !== null);

        return $reference;
    }

    private function attachUploadedFile(
        FormInterface $form,
        string $fieldName,
        object $target,
        string $setter,
        Registrant $registrant,
    ): void {
        $file = $form->get($fieldName)->getData();

        if ($file === null) {
            return;
        }

        $path = $this->uploadHandler->handle($file, $registrant->getId());
        $target->$setter($path);
    }
}
