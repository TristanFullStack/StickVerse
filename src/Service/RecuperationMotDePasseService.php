<?php

namespace App\Service;

use App\Entity\ReinitialisationMotDePasse;
use App\Entity\User;
use App\Repository\ReinitialisationMotDePasseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RecuperationMotDePasseService
{
    public function __construct(
        private UserRepository $userRepository,
        private ReinitialisationMotDePasseRepository $demandeRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $expediteur,
    ) {
    }

    public function demander(string $email): void
    {
        $utilisateur = $this->userRepository->findOneBy([
            'email' => trim($email),
        ]);

        if (!$utilisateur instanceof User) {
            return;
        }

        $this->demandeRepository->supprimerPour($utilisateur);

        $jeton = bin2hex(random_bytes(32));
        $demande = new ReinitialisationMotDePasse(
            $utilisateur,
            $jeton,
        );

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $lien = $this->urlGenerator->generate(
            'app_reinitialiser_mot_de_passe',
            ['jeton' => $jeton],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $message = (new TemplatedEmail())
            ->from($this->expediteur)
            ->to($utilisateur->getUserIdentifier())
            ->subject('Réinitialisation de ton mot de passe StickVerse')
            ->htmlTemplate('emails/reinitialisation_mot_de_passe.html.twig')
            ->context([
                'lien_reinitialisation' => $lien,
                'duree_validite_minutes' => 60,
            ]);

        $this->mailer->send($message);
    }

    public function trouverDemandeValide(
        string $jeton,
    ): ?ReinitialisationMotDePasse {
        return $this->demandeRepository
            ->trouverValideParJeton($jeton);
    }

    public function reinitialiser(
        ReinitialisationMotDePasse $demande,
        string $nouveauMotDePasse,
    ): void {
        $utilisateur = $demande->getUtilisateur();
        $utilisateur->setPassword(
            $this->passwordHasher->hashPassword(
                $utilisateur,
                $nouveauMotDePasse,
            )
        );
        $demande->utiliser();

        $this->entityManager->flush();
    }
}
