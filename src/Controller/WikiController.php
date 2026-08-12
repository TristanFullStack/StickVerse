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
        $stickmen = $stickmanRepository->findBy([
            'statutActif' => true,
        ]);

        return $this->render('wiki/index.html.twig', [
            'stickmen' => $stickmen,
        ]);
    }
}