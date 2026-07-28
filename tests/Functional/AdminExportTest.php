<?php

namespace App\Tests\Functional;

use App\DataFixtures\AdminUserFixtures;
use App\Entity\AdminUser;
use App\Entity\Enum\ProfileType;
use App\Entity\Enum\RegistrantStatus;
use App\Entity\IdentityDocument;
use App\Entity\Registrant;
use App\Entity\StudentProfile;
use App\Repository\RegistrantRepository;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminExportTest extends WebTestCase
{
    /**
     * L'accès HTTP est vérifié séparément (route protégée, bons en-têtes) : le
     * contenu du CSV, lui, est vérifié en appelant directement ExportService,
     * car le client de test de Symfony ne restitue pas fiablement le corps
     * d'une StreamedResponse écrite via un callback (comportement déjà observé
     * en dehors des tests : l'export fonctionne normalement dans un vrai navigateur).
     */
    public function testExportRouteIsProtectedAndReturnsCsvContentType(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/export?format=csv');
        self::assertResponseRedirects('/admin/login', message: 'Sans authentification, doit rediriger vers la connexion.');

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $adminUser = $em->getRepository(AdminUser::class)->findOneBy(['email' => AdminUserFixtures::DEFAULT_ADMIN_EMAIL]);
        self::assertInstanceOf(AdminUser::class, $adminUser, 'Le fixture AdminUser doit être chargé (doctrine:fixtures:load --env=test).');

        $client->loginUser($adminUser, 'admin');
        $client->request('GET', '/admin/export?format=csv');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/csv; charset=utf-8');
    }

    public function testCsvContentHasHeadersAndExpectedRowCount(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var RegistrantRepository $registrantRepository */
        $registrantRepository = static::getContainer()->get(RegistrantRepository::class);
        /** @var ExportService $exportService */
        $exportService = static::getContainer()->get(ExportService::class);

        $marker = 'EXPORTTEST'.uniqid();
        $this->persistSubmittedStudent($em, $marker.'-1');
        $this->persistSubmittedStudent($em, $marker.'-2');

        $registrants = $registrantRepository->findFilteredForExport(['search' => $marker]);
        $response = $exportService->streamCsv($registrants);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $lines = array_values(array_filter(explode("\n", trim((string) $content))));

        self::assertCount(3, $lines, 'En-tête + 2 lignes de données attendues.');
        self::assertStringContainsString('Référence', $lines[0]);
        self::assertStringContainsString('Statut passeport', $lines[0]);
    }

    private function persistSubmittedStudent(EntityManagerInterface $em, string $lastName): Registrant
    {
        $document = new IdentityDocument();
        $document
            ->setPassportNumber('P'.$lastName)
            ->setPassportIssuedAt(new \DateTimeImmutable('-2 years'))
            ->setPassportExpiresAt(new \DateTimeImmutable('+3 years'))
            ->setConsularCardNumber('C'.$lastName)
            ->setConsularCardIssuedAt(new \DateTimeImmutable('-2 years'))
            ->setConsularCardExpiresAt(new \DateTimeImmutable('+3 years'))
        ;

        $registrant = new Registrant($document);
        $registrant
            ->setEmail(strtolower($lastName).'@example.com')
            ->setLastName($lastName)
            ->setFirstName('Test')
            ->setBirthDate(new \DateTimeImmutable('-30 years'))
            ->setBirthPlace('Libreville')
            ->setBirthCountry('GA')
            ->setProfession('Testeur')
            ->setLocalPhone('+22912345678')
            ->setGabonContact('+24101234567')
            ->setFirstEntryDate(new \DateTimeImmutable('-1 years'))
            ->setProfileType(ProfileType::Student)
            ->setConsentGiven(true)
            ->setStatus(RegistrantStatus::Submitted)
            // La colonne "reference" est limitée à 20 caractères : on ne peut pas y
            // caler le marqueur de recherche (trop long), donc un identifiant court
            // dérivé du hachage du nom de famille pour rester unique par test.
            ->setReference('DG-BJ-'.substr(md5($lastName), 0, 10))
        ;

        $studentProfile = new StudentProfile();
        $studentProfile->setInstitution('Université de test');
        $registrant->setStudentProfile($studentProfile);

        $em->persist($registrant);
        $em->flush();

        return $registrant;
    }
}
