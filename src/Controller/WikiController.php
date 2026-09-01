<?php

namespace App\Controller;

use App\Repository\StickmanRepository;
use App\Service\ScorePuissanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WikiController extends AbstractController
{
    #[Route('/wiki', name: 'app_wiki')]
    public function index(
        StickmanRepository $stickmanRepository,
        ScorePuissanceService $scorePuissanceService,
    ): Response
    {
        $stickmen = $stickmanRepository->trouverDisponibles();
        $stickmen = $scorePuissanceService->trierStickmen($stickmen);
        $puissances = [];
        foreach ($stickmen as $stickman) {
            if ($stickman->getId() !== null) {
                $puissances[$stickman->getId()] = $scorePuissanceService->calculerStickman($stickman);
            }
        }

        return $this->render('wiki/index.html.twig', [
            'stickmen' => $stickmen,
            'puissances' => $puissances,
        ]);
    }

    #[Route('/wiki/{slug}', name: 'app_wiki_show')]
    public function show(
        string $slug,
        StickmanRepository $stickmanRepository,
        ScorePuissanceService $scorePuissanceService,
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
            'puissance' => $scorePuissanceService->calculerStickman($stickman),
        ]);
    }

}
