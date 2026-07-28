<?php

namespace App\Twig;

use App\Service\DocumentStatusService;
use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RegistrationExtension extends AbstractExtension
{
    public function __construct(
        private readonly DocumentStatusService $documentStatusService,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('stay_duration', $this->formatStayDuration(...)),
            new TwigFilter('country_name', $this->formatCountryName(...)),
            new TwigFilter('doc_status_badge', $this->documentStatusService->getStatusBadgeHtml(...)),
        ];
    }

    public function formatCountryName(?string $isoCode): string
    {
        if ($isoCode === null || $isoCode === '') {
            return '—';
        }

        try {
            return Countries::getName($isoCode, 'fr');
        } catch (\Throwable) {
            return $isoCode;
        }
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('document_status', $this->documentStatusService->getStatusInfo(...)),
        ];
    }

    public function formatStayDuration(?\DateTimeImmutable $from, ?\DateTimeImmutable $to = null): string
    {
        if ($from === null) {
            return '—';
        }

        $to ??= new \DateTimeImmutable('today');

        if ($from > $to) {
            return 'Date future';
        }

        $interval = $from->diff($to);
        $parts = [];

        if ($interval->y > 0) {
            $parts[] = $interval->y.' an'.($interval->y > 1 ? 's' : '');
        }
        if ($interval->m > 0) {
            $parts[] = $interval->m.' mois';
        }
        if ($interval->d > 0 || $parts === []) {
            $parts[] = $interval->d.' jour'.($interval->d > 1 ? 's' : '');
        }

        return implode(' ', $parts);
    }
}
