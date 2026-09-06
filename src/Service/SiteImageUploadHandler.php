<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Convertit une image téléversée depuis le back-office en WebP (via GD) et la
 * stocke dans public/uploads/site-images/, quel que soit son format d'origine.
 */
class SiteImageUploadHandler
{
    private const int MAX_SIZE_BYTES = 8 * 1024 * 1024;

    private const int WEBP_QUALITY = 85;

    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/site-images')]
        private readonly string $uploadsDir,
    ) {
    }

    /**
     * @throws UploadException si le fichier est trop volumineux, d'un type non
     *                         autorisé, ou si la conversion WebP échoue
     *
     * @return string le nom du fichier stocké, ex. "home_hero_1-3f9c1a2b8e0d4f5a.webp"
     */
    public function handle(UploadedFile $file, string $slot): string
    {
        if (!$file->isValid()) {
            throw new UploadException($file->getErrorMessage());
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_SIZE_BYTES) {
            throw new UploadException('Le fichier dépasse la taille maximale autorisée (8 Mo).');
        }

        $realMimeType = $this->detectRealMimeType($file->getPathname());

        if (!\in_array($realMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new UploadException(\sprintf(
                'Type de fichier non autorisé (détecté : %s). Formats acceptés : JPEG, PNG, GIF, WebP.',
                $realMimeType,
            ));
        }

        $image = $this->readImage($file->getPathname(), $realMimeType);

        if (!is_dir($this->uploadsDir) && !mkdir($this->uploadsDir, 0775, true) && !is_dir($this->uploadsDir)) {
            imagedestroy($image);

            throw new UploadException("Impossible de créer le répertoire de stockage des images.");
        }

        $filename = \sprintf('%s-%s.webp', $slot, bin2hex(random_bytes(8)));
        $success = imagewebp($image, $this->uploadsDir.\DIRECTORY_SEPARATOR.$filename, self::WEBP_QUALITY);
        imagedestroy($image);

        if (!$success) {
            throw new UploadException("La conversion de l'image au format WebP a échoué.");
        }

        return $filename;
    }

    public function delete(string $filename): void
    {
        $path = $this->uploadsDir.\DIRECTORY_SEPARATOR.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function readImage(string $pathname, string $mimeType): \GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($pathname),
            'image/png' => imagecreatefrompng($pathname),
            'image/webp' => imagecreatefromwebp($pathname),
            'image/gif' => imagecreatefromgif($pathname),
            default => false,
        };

        if (!$image instanceof \GdImage) {
            throw new UploadException("Impossible de lire le contenu de l'image.");
        }

        // Préserve la transparence des PNG/GIF/WebP lors de la conversion.
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
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
