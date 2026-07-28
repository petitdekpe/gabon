<?php

namespace App\Tests\Functional;

class RegistrationStep1ValidationTest extends AbstractRegistrationTestCase
{
    public function testInvalidSubmissionDoesNotRedirect(): void
    {
        $client = $this->createTestClient();

        $this->submitStep1($client, [
            'lastName' => '',
            'firstName' => '',
            'email' => 'not-an-email',
            'localPhone' => '0022912345678', // pas de '+' -> invalide au regard du regex E.164
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testInvalidSubmissionShowsFieldErrors(): void
    {
        $client = $this->createTestClient();

        $this->submitStep1($client, ['lastName' => '']);

        self::assertSelectorExists('md-outlined-text-field[error]');
    }

    public function testUnderageBirthDateIsRejected(): void
    {
        $client = $this->createTestClient();

        $this->submitStep1($client, [
            'birthDate' => (new \DateTimeImmutable('-10 years'))->format('Y-m-d'),
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
