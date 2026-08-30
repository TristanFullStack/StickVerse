<?php

namespace App\Controller;

use App\Repository\StickmanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WikiController extends AbstractController
{
    #[Route('/wiki', name: 'app_wiki')]
    public function index(StickmanRepository $stickmanRepository): Response
    {
        $stickmen = $stickmanRepository->trouverDisponibles();

        return $this->render('wiki/index.html.twig', [
            'stickmen' => $stickmen,
        ]);
    }

    #[Route('/wiki/{slug}', name: 'app_wiki_show')]
    public function show(
        string $slug,
        StickmanRepository $stickmanRepository
    ): Response {
        $stickman = $stickmanRepository->findOneBy([
            'slug' => $slug,
            'statutActif' => true,
        ]);

        if (!$stickman
            || $stickman->getCollectionJeu() !== null
            && !$stickman->getCollectionJeu()->estDisponibleA(new \DateTimeImmutable())) {
            throw $this->createNotFoundException('Stickman introuvable.');
        }

        return $this->render('wiki/show.html.twig', [
            'stickman' => $stickman,
        ]);
    }

}
