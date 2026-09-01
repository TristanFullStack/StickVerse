<?php

namespace App\Command;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:joueurs:reinitialiser',
    description: 'Réinitialise les cartes, équipes et ressources de joueurs sélectionnés.',
)]
final class ReinitialiserJoueursCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CombatRepository $combatRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'confirm',
            null,
            InputOption::VALUE_NONE,
            'Confirme la réinitialisation des données sélectionnées.',
        );
        $this->addOption(
            'email',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Adresse d’un joueur à réinitialiser. Répète l’option pour plusieurs comptes.',
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        if (!$input->getOption('confirm')) {
            $output->writeln(
                '<error>Action bloquée : ajoute --confirm pour confirmer le reset.</error>'
            );

            return Command::FAILURE;
        }

        /** @var list<string> $emails */
        $emails = $input->getOption('email');

        if ($emails === []) {
            $output->writeln(
                '<error>Indique au moins une adresse avec --email.</error>'
            );

            return Command::FAILURE;
        }

        $joueurs = $this->userRepository->findBy(['email' => $emails]);

        if (count($joueurs) !== count(array_unique($emails))) {
            $output->writeln(
                '<error>Une ou plusieurs adresses ne correspondent à aucun joueur.</error>'
            );

            return Command::FAILURE;
        }

        $combatsAnnules = [];

        foreach ($joueurs as $joueur) {
            foreach ($joueur->getInventaires()->toArray() as $inventaire) {
                $this->entityManager->remove($inventaire);
            }

            foreach ($joueur->getEquipes()->toArray() as $equipe) {
                $this->entityManager->remove($equipe);
            }

            $combatActif = $this->combatRepository
                ->trouverActifPourJoueur($joueur);

            if ($combatActif instanceof Combat && $combatActif->getId() !== null) {
                $combatsAnnules[$combatActif->getId()] = $combatActif;
                $combatActif
                    ->setGagnant(null)
                    ->setStatut(Combat::STATUT_ANNULE);
            }

            $joueur
                ->setPieces(User::PIECES_DEPART)
                ->setElo(User::ELO_DEPART)
                ->setCaissesPremiersRenforts(
                    User::CAISSES_PREMIERS_RENFORTS_DEPART,
                )
                ->setDateDerniereRecompenseHoraire(new DateTimeImmutable())
                ->setDateDerniereRecompenseQuotidienne(null)
                ->reinitialiserObjectifsReclames();
        }

        $this->entityManager->flush();

        $output->writeln(sprintf(
            '<info>%d joueur(s) réinitialisé(s), %d combat(s) actif(s) annulé(s).</info>',
            count($joueurs),
            count($combatsAnnules),
        ));

        return Command::SUCCESS;
    }
}
