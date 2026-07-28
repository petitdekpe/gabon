<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Form\RegistrantFilterType;
use App\Repository\RegistrantRepository;
use App\Service\AuditLogger;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    #[Route('/admin/export', name: 'admin_export', methods: ['GET'])]
    public function export(Request $request, RegistrantRepository $registrantRepository, ExportService $exportService): Response
    {
        $filterForm = $this->createForm(RegistrantFilterType::class, options: ['include_format' => true]);
        $filterForm->handleRequest($request);

        // La requête initiale (affichage du formulaire) n'a pas de paramètre "format" dans
        // sa query string ; seule la soumission du formulaire (GET) en ajoute un.
        if (!$request->query->has('format')) {
            return $this->render('admin/export.html.twig', [
                'filterForm' => $filterForm,
            ]);
        }

        $data = $filterForm->getData() ?? [];
        $format = $data['format'] ?? 'csv';
        unset($data['format']);

        $filters = array_filter($data, static fn (mixed $value): bool => $value !== null && $value !== '');

        // Journalisé avant l'exécution de la requête d'export : celle-ci renvoie un
        // itérable non bufferisé (toIterable()), qu'un flush() intercalé pourrait perturber.
        $user = $this->getUser();
        $this->auditLogger->log('registrant.export', null, $user instanceof AdminUser ? $user : null, ['format' => $format, 'filters' => $filters]);

        $registrants = $registrantRepository->findFilteredForExport($filters);

        return 'xlsx' === $format
            ? $exportService->generateExcel($registrants)
            : $exportService->streamCsv($registrants);
    }
}
