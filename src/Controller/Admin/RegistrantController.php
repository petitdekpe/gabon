<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Entity\Registrant;
use App\Form\RegistrantFilterType;
use App\Repository\RegistrantRepository;
use App\Service\AuditLogger;
use App\Service\DocumentStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN_READER')]
class RegistrantController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/diaspora')]
        private readonly string $uploadsDir,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    private function currentAdminUser(): ?AdminUser
    {
        $user = $this->getUser();

        return $user instanceof AdminUser ? $user : null;
    }

    #[Route('/admin/registrants', name: 'admin_registrant_index', methods: ['GET'])]
    public function index(Request $request, RegistrantRepository $registrantRepository): Response
    {
        $filterForm = $this->createForm(RegistrantFilterType::class, options: ['include_format' => false]);
        $filterForm->handleRequest($request);

        $filters = array_filter(
            $filterForm->getData() ?? [],
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $page = max(1, $request->query->getInt('page', 1));
        $paginator = $registrantRepository->findFiltered($filters, $page);
        $total = \count($paginator);

        return $this->render('admin/registrant_index.html.twig', [
            'filterForm' => $filterForm,
            'registrants' => $paginator,
            'page' => $page,
            'totalPages' => (int) ceil($total / RegistrantRepository::PER_PAGE),
            'total' => $total,
        ]);
    }

    #[Route('/admin/registrants/{id}', name: 'admin_registrant_show', methods: ['GET'])]
    public function show(Registrant $registrant, DocumentStatusService $documentStatusService): Response
    {
        $this->auditLogger->log('registrant.view', $registrant, $this->currentAdminUser());

        return $this->render('admin/registrant_show.html.twig', [
            'registrant' => $registrant,
            'documentStatuses' => $documentStatusService->getStatusForRegistrant($registrant),
        ]);
    }

    #[Route('/admin/registrants/{id}', name: 'admin_registrant_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Registrant $registrant,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('delete-registrant-'.$registrant->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $registrantId = (string) $registrant->getId();
        $reference = $registrant->getReference();

        $this->deleteUploadedFiles($registrantId);

        $logger->warning('Suppression d\'un dossier inscrit par un administrateur.', [
            'registrant_id' => $registrantId,
            'reference' => $reference,
            'admin_email' => $this->getUser()?->getUserIdentifier(),
        ]);

        $this->auditLogger->log('registrant.delete', $registrant, $this->currentAdminUser(), ['reference' => $reference]);

        $entityManager->remove($registrant);
        $entityManager->flush();

        $this->addFlash('success', \sprintf('Le dossier %s a été supprimé.', $reference ?? $registrantId));

        return $this->redirectToRoute('admin_registrant_index');
    }

    #[Route('/admin/file/{id}/{field}', name: 'admin_file_download', methods: ['GET'])]
    public function downloadFile(Registrant $registrant, string $field): BinaryFileResponse
    {
        $relativePath = $this->resolveFilePath($registrant, $field);

        if ($relativePath === null) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $absolutePath = $this->uploadsDir.\DIRECTORY_SEPARATOR.$relativePath;

        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Fichier introuvable sur le serveur.');
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($absolutePath));

        return $response;
    }

    #[Route('/admin/registrants/{id}/documents', name: 'admin_registrant_documents', methods: ['GET'])]
    public function downloadAllDocuments(Registrant $registrant): BinaryFileResponse
    {
        $dir = $this->uploadsDir.\DIRECTORY_SEPARATOR.$registrant->getId();

        if (!is_dir($dir)) {
            throw $this->createNotFoundException('Aucun document pour cet inscrit.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'diaspora_docs_');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $file->getFilename());
            }
        }

        $zip->close();

        $filename = \sprintf('documents_%s.zip', $registrant->getReference() ?? $registrant->getId());
        $response = new BinaryFileResponse($zipPath);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function resolveFilePath(Registrant $registrant, string $field): ?string
    {
        return match ($field) {
            'passportFile' => $registrant->getIdentityDocument()->getPassportFile(),
            'residentCardFile' => $registrant->getIdentityDocument()->getResidentCardFile(),
            'consularCardFile' => $registrant->getIdentityDocument()->getConsularCardFile(),
            'diplomaFile' => $registrant->getStudentProfile()?->getDiplomaFile(),
            'cvFile' => $registrant->getEmployeeProfile()?->getCvFile(),
            'businessPlanFile' => $registrant->getEntrepreneurProfile()?->getBusinessPlanFile(),
            default => null,
        };
    }

    private function deleteUploadedFiles(string $registrantId): void
    {
        $dir = $this->uploadsDir.\DIRECTORY_SEPARATOR.$registrantId;

        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
