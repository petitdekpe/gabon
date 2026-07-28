<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN_READER')]
class SettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/settings.html.twig');
    }
}
