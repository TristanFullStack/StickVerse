<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Service\CollectionJoueurService;
use App\Service\ScorePuissanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CollectionController extends AbstractController
{
    #[Route('/ma-collection', name: 'app_collection', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        InventaireRepository $inventaireRepository,
        EquipeRepository $equipeRepository,
        CollectionJoueurService $collectionJoueurService,
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $inventaires = $inventaireRepository->findBy([
            'utilisateur' => $utilisateur,
        ]);
        usort($inventaires, static function ($premier, $second) use ($scorePuissanceService): int {
            $difference = $scorePuissanceService->calculerStickman($second->getStickman())
                <=> $scorePuissanceService->calculerStickman($premier->getStickman());

            return $difference !== 0
                ? $difference
                : strcasecmp(
                    $premier->getStickman()->getNom() ?? '',
                    $second->getStickman()->getNom() ?? '',
                );
        });
        $puissances = [];
        foreach ($inventaires as $inventaire) {
            $stickman = $inventaire->getStickman();
            if ($stickman?->getId() !== null) {
                $puissances[$stickman->getId()] = $scorePuissanceService->calculerStickman($stickman);
            }
        }

        return $this->render('collection/index.html.twig', [
            'inventaires' => $inventaires,
            'nombre_stickmen_differents' => count($inventaires),
            'equipe_prete' => $equipeRepository->findOneBy([
                'utilisateur' => $utilisateur,
            ]) !== null,
            'collections' => $collectionJoueurService->construire($utilisateur),
            'puissances' => $puissances,
        ]);
    }
}
