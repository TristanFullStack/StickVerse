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
    name: 'app:catalogue:verifier',
    description: 'Vérifie que la base et les images correspondent au catalogue versionné.',
)]
final class VerifierInstallationCatalogueJeuCommand extends Command
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
            $totaux = $this->catalogueJeuService->verifierInstallationDepuisFichier(
                $chemin,
                $this->kernel->getProjectDir(),
            );
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(
            sprintf(
                'Installation valide : %d Stickmans, %d caisses, %d associations et %d images vérifiés.',
                $totaux['stickmans'],
                $totaux['caisses'],
                $totaux['associations'],
                $totaux['images'],
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
