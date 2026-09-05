<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class VerificationEmailService
{
    public const DUREE_VALIDITE_SECONDES = 86400;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $expediteur,
    ) {
    }

    public function envoyer(User $utilisateur): void
    {
        $jeton = bin2hex(random_bytes(32));
        $expiration = new DateTimeImmutable(
            '+'.self::DUREE_VALIDITE_SECONDES.' seconds',
        );

        $utilisateur->preparerVerificationEmail($jeton, $expiration);
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        $lien = $this->urlGenerator->generate(
            'app_verification_email',
            ['jeton' => $jeton],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $message = (new TemplatedEmail())
            ->from($this->expediteur)
            ->to($utilisateur->getUserIdentifier())
            ->subject('Confirme ton adresse e-mail StickVerse')
            ->htmlTemplate('emails/verification_email.html.twig')
            ->context([
                'lien_verification' => $lien,
                'duree_validite_heures' => 24,
            ]);

        $this->mailer->send($message);
    }

    public function confirmer(string $jeton): ?User
    {
        $utilisateur = $this->userRepository
            ->trouverParJetonVerificationEmail($jeton);

        if (!$utilisateur instanceof User) {
            return null;
        }

        $expiration = $utilisateur->getDateExpirationVerificationEmail();
        if (
            $utilisateur->isEmailVerifie()
            || $expiration === null
            || $expiration <= new DateTimeImmutable()
        ) {
            return null;
        }

        $utilisateur->confirmerEmail();
        $this->entityManager->flush();

        return $utilisateur;
    }

    public function renvoyer(string $email): void
    {
        $utilisateur = $this->userRepository->findOneBy([
            'email' => trim($email),
        ]);

        if (
            !$utilisateur instanceof User
            || $utilisateur->isEmailVerifie()
        ) {
            return;
        }

        $this->envoyer($utilisateur);
    }
}
