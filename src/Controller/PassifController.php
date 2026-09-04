<?php

namespace App\Controller;

use App\Entity\Passif;
use App\Entity\Stickman;
use App\Form\PassifType;
use App\Repository\PassifRepository;
use App\Repository\StickmanRepository;
use App\Service\PassifAffectationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/passif')]
final class PassifController extends AbstractController
{
    #[Route(name: 'app_passif_index', methods: ['GET'])]
    public function index(PassifRepository $repository): Response
    {
        return $this->render('passif/index.html.twig', [
            'passifs' => $repository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_passif_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $passif = new Passif();
        $form = $this->createForm(PassifType::class, $passif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($passif);
            $entityManager->flush();

            return $this->redirectToRoute('app_passif_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('passif/new.html.twig', ['passif' => $passif, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_passif_show', methods: ['GET'])]
    public function show(Passif $passif, StickmanRepository $stickmanRepository): Response
    {
        $stickmen = array_values(array_filter(
            $stickmanRepository->findAll(),
            static fn (Stickman $stickman): bool => array_filter(
                $stickman->getPassifs(),
                static fn (mixed $snapshot): bool => is_array($snapshot)
                    && (((isset($snapshot['id']) && (int) $snapshot['id'] === $passif->getId()))
                        || (($snapshot['type'] ?? null) === $passif->getType()
                            && ($snapshot['nom'] ?? null) === $passif->getNom())),
            ) !== [],
        ));

        return $this->render('passif/show.html.twig', [
            'passif' => $passif,
            'stickmen' => $stickmen,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_passif_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Passif $passif,
        StickmanRepository $stickmanRepository,
        PassifAffectationService $affectationService,
        EntityManagerInterface $entityManager,
    ): Response {
        $ancienType = $passif->getType();
        $ancienNom = $passif->getNom();
        $form = $this->createForm(PassifType::class, $passif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affectationService->synchroniser($passif, $stickmanRepository->findAll(), $ancienType, $ancienNom);
            $entityManager->flush();

            return $this->redirectToRoute('app_passif_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('passif/edit.html.twig', ['passif' => $passif, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_passif_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Passif $passif,
        StickmanRepository $stickmanRepository,
        PassifAffectationService $affectationService,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete_passif_'.$passif->getId(), $request->getPayload()->getString('_token'))) {
            $affectationService->retirer($passif, $stickmanRepository->findAll());
            $entityManager->remove($passif);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_passif_index', [], Response::HTTP_SEE_OTHER);
    }
}
