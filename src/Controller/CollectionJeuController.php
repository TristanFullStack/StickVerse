<?php

namespace App\Controller;

use App\Entity\CollectionJeu;
use App\Form\CollectionJeuType;
use App\Repository\CollectionJeuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/collection-jeu')]
final class CollectionJeuController extends AbstractController
{
    #[Route(name: 'app_collection_jeu_index', methods: ['GET'])]
    public function index(CollectionJeuRepository $repository): Response
    {
        return $this->render('collection_jeu/index.html.twig', [
            'collections' => $repository->findBy([], ['saison' => 'DESC', 'nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_collection_jeu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collection = new CollectionJeu();
        $form = $this->createForm(CollectionJeuType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collection);
            $entityManager->flush();

            return $this->redirectToRoute('app_collection_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collection_jeu/new.html.twig', ['collection' => $collection, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_collection_jeu_show', methods: ['GET'])]
    public function show(CollectionJeu $collection): Response
    {
        return $this->render('collection_jeu/show.html.twig', ['collection' => $collection]);
    }

    #[Route('/{id}/edit', name: 'app_collection_jeu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CollectionJeu $collection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectionJeuType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_collection_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collection_jeu/edit.html.twig', ['collection' => $collection, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_collection_jeu_delete', methods: ['POST'])]
    public function delete(Request $request, CollectionJeu $collection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collection->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($collection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collection_jeu_index', [], Response::HTTP_SEE_OTHER);
    }
}
