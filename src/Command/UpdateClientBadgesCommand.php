<?php

namespace App\Command;

use App\Service\ClientBadgeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-client-badges',
    description: 'Met à jour les badges de tous les clients basés sur leurs ventes',
)]
class UpdateClientBadgesCommand extends Command
{
    public function __construct(
        private ClientBadgeService $badgeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour des badges clients');

        $updatedCount = $this->badgeService->updateAllClientBadges();

        $io->success(sprintf('%d badges clients mis à jour avec succès!', $updatedCount));

        // Afficher les statistiques
        $stats = $this->badgeService->getBadgeStatistics();
        
        $io->section('Statistiques des badges');
        $io->table(
            ['Badge', 'Nombre de clients'],
            [
                ['🥇 Or (Gold)', $stats['gold']],
                ['🥈 Argent (Silver)', $stats['silver']],
                ['🥉 Bronze', $stats['bronze']],
                ['⚪ Aucun', $stats['none']],
                ['Total', $stats['total']],
            ]
        );

        return Command::SUCCESS;
    }
}
