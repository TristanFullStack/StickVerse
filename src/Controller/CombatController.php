<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PlanCombatType;
use App\Model\EtatEquipeCombat;
use App\Model\PlanCombat;
use App\Repository\EquipeRepository;
use App\Service\ResolutionRoundService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CombatController extends AbstractController
{
    #[Route('/combat', name: 'app_combat', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EquipeRepository $equipeRepository,
        ResolutionRoundService $resolutionRoundService,
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $equipe = $equipeRepository->findOneBy([
            'utilisateur' => $utilisateur,
        ]);

        $session = $request->getSession();

        $planJoueur1Enregistre = $session->has(
            'combat_plan_joueur1'
        );

        $planForm = $this->createForm(PlanCombatType::class);
        $planForm->handleRequest($request);

        if ($planForm->isSubmitted() && $planForm->isValid()) {
            $donnees = $planForm->getData();

            if (!is_array($donnees)) {
                throw new LogicException(
                    'Les données du plan de combat sont invalides.'
                );
            }

            /*
             * Première validation :
             * on conserve secrètement le plan du joueur 1 en session.
             */
            if (!$planJoueur1Enregistre) {
                $session->set(
                    'combat_plan_joueur1',
                    $donnees
                );

                $this->addFlash(
                    'success',
                    'Plan du joueur 1 enregistré. Au joueur 2.'
                );

                return $this->redirectToRoute('app_combat');
            }

            /*
             * Deuxième validation :
             * on récupère le plan du joueur 1 puis on construit
             * les deux objets PlanCombat.
             */
            $donneesJoueur1 = $session->get(
                'combat_plan_joueur1'
            );

            if (!is_array($donneesJoueur1)) {
                $session->remove('combat_plan_joueur1');

                throw new LogicException(
                    'Le plan du joueur 1 est introuvable.'
                );
            }

            $planJoueur1 = $this->creerPlanCombat(
                $donneesJoueur1
            );

            $planJoueur2 = $this->creerPlanCombat(
                $donnees
            );

            /*
             * Simulation locale temporaire :
             * les deux joueurs utilisent la même équipe.
             */
            $joueur1 = new EtatEquipeCombat($equipe);
            $joueur2 = new EtatEquipeCombat($equipe);

            $resultats = $resolutionRoundService->resoudre(
                $joueur1,
                $planJoueur1,
                $joueur2,
                $planJoueur2,
            );

            /*
             * Le plan secret n’est plus nécessaire
             * après la résolution du round.
             */
            $session->remove('combat_plan_joueur1');

            /*
             * On conserve temporairement le résultat afin
             * d’effectuer une redirection après le POST.
             */
            $session->set('combat_resultat', [
                'resultats' => $resultats,
                'pvJoueur1' => $joueur1->getTousLesPv(),
                'pvJoueur2' => $joueur2->getTousLesPv(),
            ]);

            return $this->redirectToRoute(
                'app_combat_resultat'
            );
        }

        return $this->render('combat/index.html.twig', [
            'equipe' => $equipe,
            'planForm' => $planForm,
            'joueurActuel' => $planJoueur1Enregistre ? 2 : 1,
        ]);
    }

    #[Route(
        '/combat/resultat',
        name: 'app_combat_resultat',
        methods: ['GET']
    )]
    public function resultat(
        Request $request,
        EquipeRepository $equipeRepository,
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $equipe = $equipeRepository->findOneBy([
            'utilisateur' => $utilisateur,
        ]);

        $session = $request->getSession();

        $resultatCombat = $session->get(
            'combat_resultat'
        );

        if (!is_array($resultatCombat)) {
            $this->addFlash(
                'error',
                'Aucun résultat de combat disponible.'
            );

            return $this->redirectToRoute('app_combat');
        }

        /*
         * Le résultat est retiré de la session après lecture.
         * Un rafraîchissement ne relancera donc pas le round.
         */
        $session->remove('combat_resultat');

        return $this->render('combat/index.html.twig', [
            'equipe' => $equipe,
            'resultats' => $resultatCombat['resultats'],
            'pvJoueur1' => $resultatCombat['pvJoueur1'],
            'pvJoueur2' => $resultatCombat['pvJoueur2'],
        ]);
    }

    /**
     * Transforme les données du formulaire en objet PlanCombat.
     *
     * @param array<string, mixed> $donnees
     */
    private function creerPlanCombat(array $donnees): PlanCombat
    {
        return new PlanCombat(
            cibleAttaqueX: $donnees['cibleAttaqueX'],
            cibleAttaqueY: $donnees['cibleAttaqueY'],
            cibleDefenseX: $donnees['cibleDefenseX'],
            cibleDefenseY: $donnees['cibleDefenseY'],
        );
    }
}