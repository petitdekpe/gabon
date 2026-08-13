<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Les champs de l'écran 1/2 sont rendus avec des Web Components MD3
 * (<md-outlined-text-field>...) et non des <input> natifs : le composant
 * Form de DomCrawler ne les reconnaît pas comme champs de formulaire.
 * On construit donc les requêtes POST "à la main" (tableau de paramètres +
 * jeton CSRF extrait du HTML), ce qui reste la façon robuste de tester
 * ce genre de formulaire.
 */
abstract class AbstractRegistrationTestCase extends WebTestCase
{
    /**
     * À utiliser à la place de static::createClient() dans ces tests : le
     * stockage du rate limiter (POST /register/step2) persiste entre les
     * exécutions de la suite, donc on le réinitialise pour l'IP par défaut
     * du client de test ('127.0.0.1') afin que ces tests restent déterministes.
     * (createClient() doit être l'appel qui démarre le kernel, d'où la
     * réinitialisation après coup plutôt que dans setUp().)
     */
    protected function createTestClient(): KernelBrowser
    {
        $client = static::createClient();

        /** @var RateLimiterFactory $limiterFactory */
        $limiterFactory = static::getContainer()->get('limiter.registration_step2');
        $limiterFactory->create('127.0.0.1')->reset();

        return $client;
    }

    /**
     * DocumentUploadHandler::handle() déplace (rename) le fichier reçu : chaque
     * appel doit donc pointer vers sa propre copie jetable, jamais le fixture
     * partagé, sous peine de le faire disparaître dès le premier upload traité.
     */
    protected function testUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'gab_test_upload_').'.png';
        copy(__DIR__.'/../Fixtures/test.png', $path);

        return new UploadedFile(
            $path,
            'document.png',
            'image/png',
            null,
            true,
        );
    }

    protected function csrfToken(Crawler $crawler, string $fieldName): string
    {
        return (string) $crawler->filter('input[name="'.$fieldName.'"]')->attr('value');
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function validStep1Payload(array $overrides = []): array
    {
        return array_replace([
            'country' => 'BJ',
            'lastName' => 'OBAME',
            'firstName' => 'Jean',
            'email' => 'jean.obame@example.com',
            'birthDate' => '1990-05-15',
            'birthPlace' => 'Libreville',
            'birthCountry' => 'GA',
            'profession' => 'Ingénieur(e)',
            'professionOther' => '',
            'localPhone' => '+2290195123456',
            'gabonContact' => '+24106031234',
            'firstEntryDate' => '2023-01-15',
            'passportNumber' => 'P123456',
            'passportIssuedAt' => '2020-01-01',
            'passportExpiresAt' => '2030-01-01',
            'residentCardNotApplicable' => '1',
            'residentCardNumber' => '',
            'residentCardIssuedAt' => '',
            'residentCardExpiresAt' => '',
            'consularCardNumber' => 'C123456',
            'consularCardIssuedAt' => '2020-01-01',
            'consularCardExpiresAt' => '2030-01-01',
        ], $overrides);
    }

    /**
     * Effectue le GET puis le POST de l'écran 1 avec des données valides
     * (sauf overrides), et laisse le client sur la réponse de la redirection.
     *
     * @param array<string, mixed> $overrides
     */
    protected function submitStep1(KernelBrowser $client, array $overrides = []): void
    {
        $crawler = $client->request('GET', '/register/step1');
        $token = $this->csrfToken($crawler, 'step1_identity[_token]');

        $payload = $this->validStep1Payload($overrides);
        $formData = ['step1_identity' => array_diff_key($payload, ['residentCardNotApplicable' => true]) + [
            '_token' => $token,
        ]];
        $formData['step1_identity']['residentCardNotApplicable'] = $payload['residentCardNotApplicable'];

        $files = ['step1_identity' => [
            'passportFile' => $this->testUploadedFile(),
            'consularCardFile' => $this->testUploadedFile(),
        ]];

        $client->request('POST', '/register/step1', $formData, $files);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function validStudentPayload(array $overrides = []): array
    {
        return array_replace([
            'institution' => 'Université de Paris',
            'wishReturnGabon' => '1',
            'targetAdministration' => '',
        ], $overrides);
    }

    protected function submitStep2Student(KernelBrowser $client, string $consentGiven = '1'): void
    {
        $crawler = $client->request('GET', '/register/step2');
        $token = $this->csrfToken($crawler, 'step2_student[_token]');

        $payload = $this->validStudentPayload();
        $formData = [
            'step2_student' => [
                'inTraining' => '',
                'endOfCycle' => '',
                'awaitingDefense' => '',
                'graduatedJobSeeking' => '',
                'institution' => $payload['institution'],
                'wishReturnGabon' => $payload['wishReturnGabon'],
                'targetAdministration' => $payload['targetAdministration'],
                'otherAdministrations' => [],
                '_token' => $token,
            ],
            'consentGiven' => $consentGiven,
        ];

        $client->request('POST', '/register/step2', $formData, [
            'step2_student' => ['diplomaFile' => null],
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function validEmployeePayload(array $overrides = []): array
    {
        return array_replace([
            'lastDegree' => 'Master',
            'firstJobYear' => '2015',
            'activitySector' => 'Commerce',
            'jobTitle' => 'Développeur',
            'currentCompany' => 'Acme Corp',
            'wishIntegrateGabon' => '1',
            'targetCompany' => '',
        ], $overrides);
    }

    protected function submitStep2Employee(KernelBrowser $client, string $consentGiven = '1'): void
    {
        $crawler = $client->request('GET', '/register/step2');
        $token = $this->csrfToken($crawler, 'step2_employee[_token]');

        $payload = $this->validEmployeePayload();
        $formData = [
            'step2_employee' => [
                'lastDegree' => $payload['lastDegree'],
                'lastDegreeOther' => '',
                'firstJobYear' => $payload['firstJobYear'],
                'activitySector' => $payload['activitySector'],
                'activitySectorOther' => '',
                'jobTitle' => $payload['jobTitle'],
                'currentCompany' => $payload['currentCompany'],
                'wishIntegrateGabon' => $payload['wishIntegrateGabon'],
                'targetCompany' => $payload['targetCompany'],
                'otherCompanies' => [],
                '_token' => $token,
            ],
            'consentGiven' => $consentGiven,
        ];

        $client->request('POST', '/register/step2', $formData, [
            'step2_employee' => ['cvFile' => null],
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function validEntrepreneurPayload(array $overrides = []): array
    {
        return array_replace([
            'companyName' => 'Disposable SARL',
            'officialCreatedAt' => '2022-06-01',
            'legalStatus' => 'SARL',
            'activitySector' => 'Commerce',
            'registrationNumber' => 'RCCM-0001',
            'hasGabonProject' => '0',
            'investmentSector' => '',
            'hasBusinessPlan' => '0',
        ], $overrides);
    }

    protected function submitStep2Entrepreneur(KernelBrowser $client, string $consentGiven = '1'): void
    {
        $crawler = $client->request('GET', '/register/step2');
        $token = $this->csrfToken($crawler, 'step2_entrepreneur[_token]');

        $payload = $this->validEntrepreneurPayload();
        $formData = [
            'step2_entrepreneur' => [
                'companyName' => $payload['companyName'],
                'officialCreatedAt' => $payload['officialCreatedAt'],
                'legalStatus' => $payload['legalStatus'],
                'activitySector' => $payload['activitySector'],
                'activitySectorOther' => '',
                'registrationNumber' => $payload['registrationNumber'],
                'hasGabonProject' => $payload['hasGabonProject'],
                'investmentSector' => $payload['investmentSector'],
                'hasBusinessPlan' => $payload['hasBusinessPlan'],
                'otherTargets' => [],
                '_token' => $token,
            ],
            'consentGiven' => $consentGiven,
        ];

        $client->request('POST', '/register/step2', $formData, [
            'step2_entrepreneur' => ['businessPlanFile' => null],
        ]);
    }
}
