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
            new TwigFilter('country_flag', $this->formatCountryFlag(...)),
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

    /**
     * Convertit un code ISO 3166-1 alpha-2 (ex. "GA") en emoji drapeau (🇬🇦)
     * en combinant les deux "regional indicator symbols" Unicode correspondants.
     */
    public function formatCountryFlag(?string $isoCode): string
    {
        if ($isoCode === null || 2 !== \strlen($isoCode)) {
            return '';
        }

        $isoCode = strtoupper($isoCode);
        if (!ctype_alpha($isoCode)) {
            return '';
        }

        $flag = '';
        foreach (str_split($isoCode) as $letter) {
            $flag .= mb_chr(127397 + \ord($letter), 'UTF-8');
        }

        return $flag;
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
