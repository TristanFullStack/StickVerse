<?php

namespace App\Command;

use App\Service\NettoyageCombatsExpiresService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:combats:expirer',
    description: 'Applique les expirations aux combats actifs abandonnés.',
)]
final class ExpirerCombatsEnLigneCommand extends Command
{
    public function __construct(
        private readonly NettoyageCombatsExpiresService $nettoyageService,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $totaux = $this->nettoyageService->nettoyer();

        $io->success(
            sprintf(
                "Nettoyage terminé.\n"
                ."Combats examinés : %d\n"
                ."Attentes annulées : %d\n"
                ."Préparations annulées : %d\n"
                ."Forfaits de préparation : %d\n"
                .'Forfaits de plan : %d',
                $totaux['examines'],
                $totaux['annulesAttente'],
                $totaux['annulesPreparation'],
                $totaux['forfaitsPreparation'],
                $totaux['forfaitsPlan'],
            )
        );

        return Command::SUCCESS;
    }
}
