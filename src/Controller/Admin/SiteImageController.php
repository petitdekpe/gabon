<?php

namespace App\Controller\Admin;

use App\Config\SiteImageCatalog;
use App\Entity\AdminUser;
use App\Entity\SiteImage;
use App\Repository\SiteImageRepository;
use App\Service\AuditLogger;
use App\Service\SiteImageUploadHandler;
use App\Service\UploadException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN_READER')]
class SiteImageController extends AbstractController
{
    public function __construct(
        private readonly SiteImageRepository $siteImageRepository,
        private readonly SiteImageUploadHandler $uploadHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    private function currentAdminUser(): ?AdminUser
    {
        $user = $this->getUser();

        return $user instanceof AdminUser ? $user : null;
    }

    #[Route('/admin/images', name: 'admin_site_images', methods: ['GET'])]
    public function index(): Response
    {
        $overrides = $this->siteImageRepository->findAllIndexedBySlot();

        $slots = [];
        foreach (SiteImageCatalog::all() as $slot => $definition) {
            $override = $overrides[$slot] ?? null;
            $slots[] = [
                'slot' => $slot,
                'label' => $definition['label'],
                'url' => $override !== null ? '/uploads/site-images/'.$override->getFilename() : $definition['default'],
                'isOverridden' => $override !== null,
                'updatedAt' => $override?->getUpdatedAt(),
                'updatedBy' => $override?->getUpdatedBy()?->getEmail(),
            ];
        }

        return $this->render('admin/site_images.html.twig', [
            'slots' => $slots,
        ]);
    }

    #[Route('/admin/images/{slot}', name: 'admin_site_image_upload', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(string $slot, Request $request): Response
    {
        if (!SiteImageCatalog::exists($slot)) {
            throw $this->createNotFoundException("Emplacement d'image inconnu.");
        }

        if (!$this->isCsrfTokenValid('site-image-upload-'.$slot, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $file = $request->files->get('image');

        if ($file === null) {
            $this->addFlash('error', 'Merci de sélectionner un fichier image.');

            return $this->redirectToRoute('admin_site_images');
        }

        try {
            $filename = $this->uploadHandler->handle($file, $slot);
        } catch (UploadException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('admin_site_images');
        }

        $siteImage = $this->siteImageRepository->findBySlot($slot);
        $previousFilename = $siteImage?->getFilename();

        if ($siteImage === null) {
            $siteImage = new SiteImage($slot);
            $this->entityManager->persist($siteImage);
        }

        $siteImage
            ->setFilename($filename)
            ->setOriginalFilename($file->getClientOriginalName())
            ->setUpdatedBy($this->currentAdminUser())
            ->touch();

        $this->entityManager->flush();

        if ($previousFilename !== null) {
            $this->uploadHandler->delete($previousFilename);
        }

        $this->auditLogger->log('site_image.update', $siteImage, $this->currentAdminUser(), ['slot' => $slot]);

        $this->addFlash('success', \sprintf('Image « %s » mise à jour.', SiteImageCatalog::getLabel($slot)));

        return $this->redirectToRoute('admin_site_images');
    }

    #[Route('/admin/images/{slot}/reset', name: 'admin_site_image_reset', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reset(string $slot, Request $request): Response
    {
        if (!SiteImageCatalog::exists($slot)) {
            throw $this->createNotFoundException("Emplacement d'image inconnu.");
        }

        if (!$this->isCsrfTokenValid('site-image-reset-'.$slot, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $siteImage = $this->siteImageRepository->findBySlot($slot);

        if ($siteImage === null) {
            $this->addFlash('info', "Cette image est déjà celle par défaut.");

            return $this->redirectToRoute('admin_site_images');
        }

        $this->uploadHandler->delete($siteImage->getFilename());
        $this->auditLogger->log('site_image.reset', $siteImage, $this->currentAdminUser(), ['slot' => $slot]);

        $this->entityManager->remove($siteImage);
        $this->entityManager->flush();

        $this->addFlash('success', \sprintf('Image « %s » réinitialisée à sa valeur par défaut.', SiteImageCatalog::getLabel($slot)));

        return $this->redirectToRoute('admin_site_images');
    }
}
