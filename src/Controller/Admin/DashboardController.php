<?php

namespace App\Controller\Admin;

use App\Repository\RegistrantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN_READER')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(RegistrantRepository $registrantRepository): Response
    {
        $profileCounts = $registrantRepository->countByProfileType();

        return $this->render('admin/dashboard.html.twig', [
            'totalSubmitted' => $registrantRepository->countSubmitted(),
            'profileCounts' => $profileCounts,
            'profileCountsMax' => max([1, ...array_values($profileCounts)]),
            'expiringSoon' => $registrantRepository->countExpiringWithinDays(30),
            'submittedThisMonth' => $registrantRepository->countSubmittedSince(
                new \DateTimeImmutable('first day of this month 00:00:00'),
            ),
            'recentRegistrants' => $registrantRepository->findMostRecent(10),
            'currentMonthLabel' => (new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, pattern: 'MMMM yyyy'))->format(new \DateTimeImmutable()),
        ]);
    }
}
