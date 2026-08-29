<?php

namespace App\Command;

use App\Service\CatalogueJeuService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:catalogue:importer',
    description: 'Crée ou met à jour le catalogue depuis son export JSON.',
)]
final class ImporterCatalogueJeuCommand extends Command
{
    public function __construct(
        private readonly CatalogueJeuService $catalogueJeuService,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'fichier',
            InputArgument::OPTIONAL,
            'Chemin du fichier JSON, relatif à la racine du projet ou absolu.',
            'data/catalogue.json',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fichier = (string) $input->getArgument('fichier');
        $chemin = $this->resoudreChemin($fichier);

        try {
            $totaux = $this->catalogueJeuService->importerDepuisFichier($chemin);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            sprintf(
                'Catalogue importé : %d Stickmans, %d caisses et %d associations traités.',
                $totaux['stickmans'],
                $totaux['caisses'],
                $totaux['associations'],
            ),
        );

        return Command::SUCCESS;
    }

    private function resoudreChemin(string $fichier): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/])/', $fichier)) {
            return $fichier;
        }

        return $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.$fichier;
    }
}
