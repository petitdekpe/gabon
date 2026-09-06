<?php

namespace App\Tests\Functional;

use App\DataFixtures\AdminUserFixtures;
use App\Entity\AdminUser;
use App\Repository\SiteImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminSiteImageTest extends WebTestCase
{
    private const string SLOT = 'home_hero_1';

    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        /** @var SiteImageRepository $repository */
        $repository = static::getContainer()->get(SiteImageRepository::class);

        $siteImage = $repository->findBySlot(self::SLOT);

        if ($siteImage !== null) {
            $path = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads/site-images/'.$siteImage->getFilename();
            if (is_file($path)) {
                unlink($path);
            }

            $em->remove($siteImage);
            $em->flush();
        }

        parent::tearDown();
    }

    public function testIndexRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/images');
        self::assertResponseRedirects('/admin/login', message: 'Sans authentification, doit rediriger vers la connexion.');
    }

    public function testUploadConvertsToWebpAndIsServedByThePublicSite(): void
    {
        $client = static::createClient();
        $adminUser = $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/images');
        self::assertResponseIsSuccessful();

        $token = (string) $crawler
            ->filter('form[action$="/admin/images/'.self::SLOT.'"] input[name="_token"]')
            ->attr('value');
        self::assertNotSame('', $token, 'Le formulaire de remplacement de '.self::SLOT.' doit exposer un jeton CSRF.');

        $client->request('POST', '/admin/images/'.self::SLOT, ['_token' => $token], [
            'image' => $this->testUploadedFile(),
        ]);

        self::assertResponseRedirects('/admin/images');

        /** @var SiteImageRepository $repository */
        $repository = static::getContainer()->get(SiteImageRepository::class);
        $siteImage = $repository->findBySlot(self::SLOT);

        self::assertNotNull($siteImage, "L'upload doit créer une ligne SiteImage pour le slot.");
        self::assertStringEndsWith('.webp', $siteImage->getFilename());
        self::assertSame((string) $adminUser->getId(), (string) $siteImage->getUpdatedBy()?->getId());

        $absolutePath = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads/site-images/'.$siteImage->getFilename();
        self::assertFileExists($absolutePath, 'Le fichier WebP converti doit être écrit sur le disque.');

        $info = getimagesize($absolutePath);
        self::assertNotFalse($info);
        self::assertSame('image/webp', $info['mime'], "Le fichier stocké doit réellement être un WebP, quel que soit le format d'origine.");

        // La page d'accueil doit désormais pointer vers l'image personnalisée.
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('/uploads/site-images/'.$siteImage->getFilename(), (string) $client->getResponse()->getContent());
    }

    public function testResetRestoresTheDefaultImage(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/images');
        $uploadToken = (string) $crawler
            ->filter('form[action$="/admin/images/'.self::SLOT.'"] input[name="_token"]')
            ->attr('value');

        $client->request('POST', '/admin/images/'.self::SLOT, ['_token' => $uploadToken], [
            'image' => $this->testUploadedFile(),
        ]);

        /** @var SiteImageRepository $repository */
        $repository = static::getContainer()->get(SiteImageRepository::class);
        $siteImage = $repository->findBySlot(self::SLOT);
        self::assertNotNull($siteImage);
        $absolutePath = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads/site-images/'.$siteImage->getFilename();

        $crawler = $client->request('GET', '/admin/images');
        $resetToken = (string) $crawler
            ->filter('form[action$="/admin/images/'.self::SLOT.'/reset"] input[name="_token"]')
            ->attr('value');
        self::assertNotSame('', $resetToken, 'Une image personnalisée doit exposer un formulaire de réinitialisation.');

        $client->request('POST', '/admin/images/'.self::SLOT.'/reset', ['_token' => $resetToken]);
        self::assertResponseRedirects('/admin/images');

        self::assertNull($repository->findBySlot(self::SLOT), "La ligne SiteImage doit disparaître après réinitialisation.");
        self::assertFileDoesNotExist($absolutePath, 'Le fichier WebP doit être supprimé du disque après réinitialisation.');
    }

    private function loginAsAdmin(KernelBrowser $client): AdminUser
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $adminUser = $em->getRepository(AdminUser::class)->findOneBy(['email' => AdminUserFixtures::DEFAULT_ADMIN_EMAIL]);
        self::assertInstanceOf(AdminUser::class, $adminUser, 'Le fixture AdminUser doit être chargé (doctrine:fixtures:load --env=test).');

        $client->loginUser($adminUser, 'admin');

        return $adminUser;
    }

    /**
     * DocumentUploadHandler/SiteImageUploadHandler déplacent (rename) le fichier
     * reçu : chaque appel doit donc pointer vers sa propre copie jetable.
     */
    private function testUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'gab_test_site_image_').'.png';
        copy(__DIR__.'/../Fixtures/test.png', $path);

        return new UploadedFile(
            $path,
            'photo.png',
            'image/png',
            null,
            true,
        );
    }
}
