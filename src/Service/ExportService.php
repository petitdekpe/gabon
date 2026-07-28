<?php

namespace App\Service;

use App\Entity\Registrant;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère les exports CSV (league/csv, en flux) et Excel (PhpSpreadsheet, fichier temporaire)
 * de la liste des inscrits filtrée.
 */
class ExportService
{
    private const array HEADERS = [
        'Référence',
        'Nom',
        'Prénoms',
        'Pays de résidence',
        'Profil',
        'Date de naissance',
        'Lieu de naissance',
        'Profession',
        'Téléphone local',
        'Contact Gabon',
        '1ère entrée',
        'Statut passeport',
        'Expiration passeport',
        'Statut carte consulaire',
        'Expiration carte consulaire',
        "Date d'inscription",
    ];

    public function __construct(
        private readonly DocumentStatusService $documentStatusService,
    ) {
    }

    /**
     * @param iterable<Registrant> $registrants
     */
    public function streamCsv(iterable $registrants): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($registrants): void {
            // Écrit dans un buffer en mémoire (pas directement sur php://output) : plus
            // robuste face aux différentes couches qui peuvent intercepter la sortie
            // (ob_start imbriqués, clients de test, etc.) que fwrite() direct.
            $csv = Writer::fromString('');
            $csv->insertOne(self::HEADERS);

            foreach ($registrants as $registrant) {
                $csv->insertOne($this->mapRow($registrant));
            }

            echo $csv->toString();
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->buildFilename('csv')),
        );

        return $response;
    }

    /**
     * @param iterable<Registrant> $registrants
     */
    public function generateExcel(iterable $registrants): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inscrits');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $rowIndex = 2;
        foreach ($registrants as $registrant) {
            $sheet->fromArray($this->mapRow($registrant), null, 'A'.$rowIndex);
            ++$rowIndex;
        }

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'diaspora_export_');
        (new Xlsx($spreadsheet))->save($tmpFile);

        $response = new BinaryFileResponse($tmpFile);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->buildFilename('xlsx'));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return list<string>
     */
    private function mapRow(Registrant $registrant): array
    {
        $document = $registrant->getIdentityDocument();
        $passportStatus = $this->documentStatusService->getStatusInfo($document->getPassportExpiresAt());
        $consularStatus = $this->documentStatusService->getStatusInfo($document->getConsularCardExpiresAt());

        return [
            (string) $registrant->getReference(),
            (string) $registrant->getLastName(),
            (string) $registrant->getFirstName(),
            $registrant->getCountry(),
            $registrant->getProfileType()?->value ?? '',
            $registrant->getBirthDate()?->format('Y-m-d') ?? '',
            (string) $registrant->getBirthPlace(),
            (string) $registrant->getProfession(),
            (string) $registrant->getLocalPhone(),
            (string) $registrant->getGabonContact(),
            $registrant->getFirstEntryDate()?->format('Y-m-d') ?? '',
            $passportStatus['label'],
            $document->getPassportExpiresAt()?->format('Y-m-d') ?? '',
            $consularStatus['label'],
            $document->getConsularCardExpiresAt()?->format('Y-m-d') ?? '',
            $registrant->getCreatedAt()->format('Y-m-d H:i'),
        ];
    }

    private function buildFilename(string $extension): string
    {
        return \sprintf('diaspora_export_%s.%s', (new \DateTimeImmutable())->format('Ymd'), $extension);
    }
}
