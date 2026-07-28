<?php

namespace App\Tests\Functional;

use App\Entity\Enum\RegistrantStatus;
use App\Entity\Registrant;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationStep2StudentTest extends AbstractRegistrationTestCase
{
    public function testCompleteStudentProfileIsPersistedWithReference(): void
    {
        $client = $this->createTestClient();
        $email = 'student.'.uniqid().'@example.com';

        $this->submitStep1($client, ['email' => $email]);
        self::assertResponseRedirects('/register/step2');

        $this->submitStep2Student($client);
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.gab-confirmation__reference', 'DG-BJ-');

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $registrant = $em->getRepository(Registrant::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(Registrant::class, $registrant);
        self::assertSame(RegistrantStatus::Submitted, $registrant->getStatus());
        self::assertNotNull($registrant->getReference());
        self::assertNotNull($registrant->getStudentProfile());
        self::assertSame('Université de Paris', $registrant->getStudentProfile()->getInstitution());
    }
}
