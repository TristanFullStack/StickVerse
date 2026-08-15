<?php

namespace App\Controller;

use App\Repository\CaisseRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CaissePubliqueController extends AbstractController
{
    #[Route('/caisses', name: 'app_caisse_publique')]
    public function index(CaisseRepository $caisseRepository): Response
    {
        $caisses = $caisseRepository->findBy([
            'statutActif' => true,
        ]);

        return $this->render('caisse_publique/index.html.twig', [
            'caisses' => $caisses,
        ]);
    }
}
