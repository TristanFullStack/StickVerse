<?php

namespace App\Controller;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombattantCombatRepository;
use App\Repository\ResultatRoundCombatRepository;
use App\Security\Voter\CombatVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InterfaceCombatEnLigneController extends AbstractController
{
    #[Route(
        '/combats',
        name: 'app_combats_en_ligne',
        methods: ['GET']
    )]
    public function index(): Response
    {
        return $this->render(
            'combat_en_ligne/index.html.twig'
        );
    }

    #[Route(
        '/combats/{id}/rapport',
        name: 'app_rapport_combat_en_ligne',
        methods: ['GET']
    )]
    public function rapport(
        Combat $combat,
        CombattantCombatRepository $combattantRepository,
        ResultatRoundCombatRepository $resultatRoundRepository,
    ): Response {
        $this->denyAccessUnlessGranted(
            CombatVoter::CONSULTER,
            $combat,
        );

        if (!$combat->estTermine()
            && $combat->getStatut() !== Combat::STATUT_ABANDONNE
            && $combat->getStatut() !== Combat::STATUT_FORFAIT
        ) {
            throw $this->createNotFoundException(
                'Le rapport est disponible à la fin du combat.'
            );
        }

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $adversaire = $combat->getJoueur1() === $utilisateur
            ? $combat->getJoueur2()
            : $combat->getJoueur1();

        if (!$adversaire instanceof User) {
            throw $this->createNotFoundException(
                'Le combat ne possède pas d’adversaire.'
            );
        }

        [$resultatCode, $resultatLibelle] = $this->determinerResultat(
            $combat,
            $utilisateur,
        );

        return $this->render(
            'combat_en_ligne/rapport.html.twig',
            [
                'combat' => $combat,
                'moi' => $utilisateur,
                'adversaire' => $adversaire,
                'resultatCode' => $resultatCode,
                'resultatLibelle' => $resultatLibelle,
                'combattantsMoi' => $combattantRepository
                    ->trouverPourCombatEtJoueur($combat, $utilisateur),
                'combattantsAdversaire' => $combattantRepository
                    ->trouverPourCombatEtJoueur($combat, $adversaire),
                'rounds' => $resultatRoundRepository
                    ->trouverPourCombat($combat),
            ],
        );
    }

    /**
     * @return array{string, string}
     */
    private function determinerResultat(
        Combat $combat,
        User $utilisateur,
    ): array {
        if ($combat->getGagnant() === null) {
            return ['egalite', 'Match nul'];
        }

        if ($combat->getGagnant() === $utilisateur) {
            if ($combat->getStatut() === Combat::STATUT_FORFAIT) {
                return ['victoire', 'Victoire par forfait'];
            }

            return $combat->getStatut() === Combat::STATUT_ABANDONNE
                ? ['victoire', 'Victoire par abandon']
                : ['victoire', 'Victoire'];
        }

        if ($combat->getStatut() === Combat::STATUT_FORFAIT) {
            return ['defaite', 'Défaite par forfait'];
        }

        return $combat->getStatut() === Combat::STATUT_ABANDONNE
            ? ['defaite', 'Abandon']
            : ['defaite', 'Défaite'];
    }
}
