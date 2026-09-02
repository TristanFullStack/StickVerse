<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Form\ActualiteType;
use App\Repository\ActualiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/actualite')]
final class ActualiteAdminController extends AbstractController
{
    #[Route(name: 'app_actualite_admin_index', methods: ['GET'])]
    public function index(ActualiteRepository $repository): Response { return $this->render('actualite_admin/index.html.twig', ['actualites' => $repository->findBy([], ['id' => 'DESC'])]); }

    #[Route('/new', name: 'app_actualite_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $actualite = new Actualite();
        $form = $this->createForm(ActualiteType::class, $actualite); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $em->persist($actualite); $em->flush(); return $this->redirectToRoute('app_actualite_admin_index'); }
        return $this->render('actualite_admin/form.html.twig', ['form' => $form, 'actualite' => $actualite, 'titre' => 'Nouvelle actualité']);
    }

    #[Route('/{id}/edit', name: 'app_actualite_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Actualite $actualite, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ActualiteType::class, $actualite); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $em->flush(); return $this->redirectToRoute('app_actualite_admin_index'); }
        return $this->render('actualite_admin/form.html.twig', ['form' => $form, 'actualite' => $actualite, 'titre' => 'Modifier l’actualité']);
    }

    #[Route('/{id}/delete', name: 'app_actualite_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Actualite $actualite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_actualite_'.$actualite->getId(), $request->getPayload()->getString('_token'))) { $em->remove($actualite); $em->flush(); }
        return $this->redirectToRoute('app_actualite_admin_index');
    }
}
