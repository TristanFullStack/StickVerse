<?php

namespace App\Controller;

use App\Entity\Stickman;
use App\Form\StickmanType;
use App\Repository\StickmanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stickman')]
final class StickmanController extends AbstractController
{
    #[Route(name: 'app_stickman_index', methods: ['GET'])]
    public function index(StickmanRepository $stickmanRepository): Response
    {
        return $this->render('stickman/index.html.twig', [
            'stickmen' => $stickmanRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_stickman_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stickman = new Stickman();
        $form = $this->createForm(StickmanType::class, $stickman);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stickman);
            $entityManager->flush();

            return $this->redirectToRoute('app_stickman_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stickman/new.html.twig', [
            'stickman' => $stickman,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stickman_show', methods: ['GET'])]
    public function show(Stickman $stickman): Response
    {
        return $this->render('stickman/show.html.twig', [
            'stickman' => $stickman,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stickman_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stickman $stickman, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StickmanType::class, $stickman);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_stickman_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stickman/edit.html.twig', [
            'stickman' => $stickman,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stickman_delete', methods: ['POST'])]
    public function delete(Request $request, Stickman $stickman, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stickman->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stickman);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_stickman_index', [], Response::HTTP_SEE_OTHER);
    }
}
