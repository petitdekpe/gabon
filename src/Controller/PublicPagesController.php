<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicPagesController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'privacy_policy', methods: ['GET'])]
    public function privacyPolicy(): Response
    {
        return $this->render('legal/privacy_policy.html.twig');
    }

    /**
     * Servi par un contrôleur (plutôt qu'un fichier statique dans public/) pour
     * garantir le bon Content-Type quel que soit le serveur web utilisé : la
     * table MIME du serveur intégré de PHP, par exemple, ne connaît pas
     * l'extension .webmanifest et sert le fichier en text/html.
     */
    #[Route('/manifest.webmanifest', name: 'pwa_manifest', methods: ['GET'])]
    public function manifest(): Response
    {
        return $this->render('pwa/manifest.webmanifest.twig', [], new Response(
            headers: ['Content-Type' => 'application/manifest+json'],
        ));
    }

    /**
     * Un Content-Type JavaScript correct est impératif ici : les navigateurs
     * refusent d'enregistrer un service worker dont la réponse n'est pas
     * servie en text/javascript ou équivalent.
     */
    #[Route('/sw.js', name: 'pwa_service_worker', methods: ['GET'])]
    public function serviceWorker(): Response
    {
        return $this->render('pwa/sw.js.twig', [], new Response(
            headers: [
                'Content-Type' => 'text/javascript; charset=utf-8',
                'Service-Worker-Allowed' => '/',
                'Cache-Control' => 'no-cache',
            ],
        ));
    }
}
