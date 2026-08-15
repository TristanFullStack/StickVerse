<?php

namespace App\Controller;

use App\Entity\CaisseStickman;
use App\Form\CaisseStickmanType;
use App\Repository\CaisseStickmanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/caisse-stickman')]
final class CaisseStickmanController extends AbstractController
{
    #[Route(name: 'app_caisse_stickman_index', methods: ['GET'])]
    public function index(CaisseStickmanRepository $caisseStickmanRepository): Response
    {
        return $this->render('caisse_stickman/index.html.twig', [
            'caisse_stickmen' => $caisseStickmanRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_caisse_stickman_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $caisseStickman = new CaisseStickman();
        $form = $this->createForm(CaisseStickmanType::class, $caisseStickman);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($caisseStickman);
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_stickman_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse_stickman/new.html.twig', [
            'caisse_stickman' => $caisseStickman,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_stickman_show', methods: ['GET'])]
    public function show(CaisseStickman $caisseStickman): Response
    {
        return $this->render('caisse_stickman/show.html.twig', [
            'caisse_stickman' => $caisseStickman,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_stickman_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CaisseStickman $caisseStickman, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CaisseStickmanType::class, $caisseStickman);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_stickman_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse_stickman/edit.html.twig', [
            'caisse_stickman' => $caisseStickman,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_stickman_delete', methods: ['POST'])]
    public function delete(Request $request, CaisseStickman $caisseStickman, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$caisseStickman->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($caisseStickman);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_caisse_stickman_index', [], Response::HTTP_SEE_OTHER);
    }
}
