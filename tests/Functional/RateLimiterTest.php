<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Limite : 10 soumissions / IP / heure sur POST /register/step2 (symfony/rate-limiter).
 * Le compteur de l'IP factice est explicitement réinitialisé avant le test pour rester
 * déterministe d'une exécution à l'autre (le stockage du limiteur persiste entre les runs).
 */
class RateLimiterTest extends WebTestCase
{
    public function testEleventhSubmissionFromSameIpIsRateLimited(): void
    {
        $client = static::createClient();
        $ip = '198.51.100.42';
        $server = ['REMOTE_ADDR' => $ip];

        /** @var RateLimiterFactory $limiterFactory */
        $limiterFactory = static::getContainer()->get('limiter.registration_step2');
        $limiterFactory->create($ip)->reset();

        for ($i = 0; $i < 10; ++$i) {
            $client->request('POST', '/register/step2', server: $server);
            self::assertNotSame(429, $client->getResponse()->getStatusCode(), \sprintf('La requête n°%d ne devrait pas encore être limitée.', $i + 1));
        }

        $client->request('POST', '/register/step2', server: $server);

        self::assertResponseStatusCodeSame(429);
    }
}
