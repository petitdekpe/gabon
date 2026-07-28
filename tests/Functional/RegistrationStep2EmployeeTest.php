<?php

namespace App\Tests\Functional;

use App\Entity\Enum\RegistrantStatus;
use App\Entity\Registrant;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationStep2EmployeeTest extends AbstractRegistrationTestCase
{
    public function testCompleteEmployeeProfileIsPersistedWithReference(): void
    {
        $client = $this->createTestClient();
        $email = 'employee.'.uniqid().'@example.com';

        $this->submitStep1($client, ['email' => $email]);
        self::assertResponseRedirects('/register/step2');

        $this->submitStep2Employee($client);
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertResponseIsSuccessful();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $registrant = $em->getRepository(Registrant::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(Registrant::class, $registrant);
        self::assertSame(RegistrantStatus::Submitted, $registrant->getStatus());
        self::assertNotNull($registrant->getReference());
        self::assertNotNull($registrant->getEmployeeProfile());
        self::assertSame('Développeur', $registrant->getEmployeeProfile()->getJobTitle());
        self::assertSame(2015, $registrant->getEmployeeProfile()->getFirstJobYear());
    }
}
