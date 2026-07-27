<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Déplace un fichier téléversé vers var/uploads/diaspora/{uuid}/ après vérification
 * de sa taille et de son type MIME réel (magic bytes, via finfo), indépendamment
 * de l'extension ou du Content-Type déclarés par le navigateur.
 */
class DocumentUploadHandler
{
    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const array ALLOWED_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/diaspora')]
        private readonly string $uploadsDir,
    ) {
    }

    /**
     * @throws UploadException si le fichier est trop volumineux ou d'un type non autorisé
     *
     * @return string le chemin relatif stocké dans l'entité, ex. "{uuid}/3f9c1a2b8e0d4f5a.pdf"
     */
    public function handle(UploadedFile $file, Uuid $registrantId): string
    {
        if (!$file->isValid()) {
            throw new UploadException($file->getErrorMessage());
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new UploadException('Le fichier dépasse la taille maximale autorisée (5 Mo).');
        }

        $realMimeType = $this->detectRealMimeType($file->getPathname());

        if (!isset(self::ALLOWED_MIME_TYPES[$realMimeType])) {
            throw new UploadException(sprintf('Type de fichier non autorisé (détecté : %s). Formats acceptés : PDF, JPEG, PNG.', $realMimeType));
        }

        $targetDir = $this->uploadsDir.\DIRECTORY_SEPARATOR.$registrantId;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new UploadException("Impossible de créer le répertoire d'upload.");
        }

        $filename = sprintf('%s.%s', bin2hex(random_bytes(16)), self::ALLOWED_MIME_TYPES[$realMimeType]);
        $file->move($targetDir, $filename);

        return $registrantId.'/'.$filename;
    }

    private function detectRealMimeType(string $pathname): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new UploadException("Impossible d'analyser le type du fichier.");
        }

        $mimeType = finfo_file($finfo, $pathname);
        finfo_close($finfo);

        if ($mimeType === false) {
            throw new UploadException("Impossible d'analyser le type du fichier.");
        }

        return $mimeType;
    }
}
