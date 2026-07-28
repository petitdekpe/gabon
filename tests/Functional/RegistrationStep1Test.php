<?php

namespace App\Tests\Functional;

class RegistrationStep1Test extends AbstractRegistrationTestCase
{
    public function testValidSubmissionRedirectsToStep2(): void
    {
        $client = $this->createTestClient();

        $this->submitStep1($client);

        self::assertResponseRedirects('/register/step2');
    }

    public function testStep2PageIsReachableAfterValidSubmission(): void
    {
        $client = $this->createTestClient();

        $this->submitStep1($client);
        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Votre profil');
    }
}
