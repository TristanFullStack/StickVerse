<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActualiteController extends AbstractController
{
    #[Route('/actualites', name: 'app_actualite_index', methods: ['GET'])]
    public function index(ActualiteRepository $repository): Response
    {
        return $this->render('actualite/index.html.twig', ['actualites' => $repository->trouverPubliees()]);
    }

    #[Route('/actualites/{slug}', name: 'app_actualite_show', methods: ['GET'])]
    public function show(string $slug, ActualiteRepository $repository): Response
    {
        $actualite = $repository->findOneBy(['slug' => $slug, 'statutActif' => true]);
        if ($actualite === null || ($actualite->getDatePublication() !== null && $actualite->getDatePublication() > new \DateTimeImmutable())) {
            throw $this->createNotFoundException('Actualité introuvable.');
        }
        return $this->render('actualite/show.html.twig', ['actualite' => $actualite]);
    }
}
