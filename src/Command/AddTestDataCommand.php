<?php

namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-test-data',
    description: 'Ajoute des données de test (date de naissance et sexe) aux utilisateurs existants',
)]
class AddTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Ajout de données de test pour l\'analyse IA');

        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(Utilisateur::class)->findAll();

        if (empty($users)) {
            $io->error('Aucun utilisateur trouvé dans la base de données.');
            return Command::FAILURE;
        }

        $io->info(sprintf('Nombre d\'utilisateurs trouvés : %d', count($users)));

        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Vérifier si l'utilisateur a déjà des données
            if ($user->getDateNaissance() && $user->getSexe()) {
                $io->text(sprintf(
                    '⏭️  %s %s - Déjà configuré (âge: %d ans, sexe: %s)',
                    $user->getPrenom(),
                    $user->getNom(),
                    $user->getAge(),
                    $user->getSexe()
                ));
                $skipped++;
                continue;
            }

            // Générer une date de naissance aléatoire (entre 18 et 65 ans)
            $age = rand(18, 65);
            $dateNaissance = new \DateTime();
            $dateNaissance->modify("-{$age} years");
            $dateNaissance->modify('-' . rand(0, 364) . ' days'); // Ajouter des jours aléatoires

            // Générer un sexe aléatoire
            $sexes = ['homme', 'femme', 'homme', 'femme', 'autre']; // Plus de chances pour homme/femme
            $sexe = $sexes[array_rand($sexes)];

            // Mettre à jour l'utilisateur
            $user->setDateNaissance($dateNaissance);
            $user->setSexe($sexe);

            $io->text(sprintf(
                '✅ %s %s - Date: %s (âge: %d ans), Sexe: %s',
                $user->getPrenom(),
                $user->getNom(),
                $dateNaissance->format('d/m/Y'),
                $user->getAge(),
                $sexe
            ));

            $updated++;
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();

        $io->newLine();
        $io->success([
            sprintf('✅ %d utilisateur(s) mis à jour', $updated),
            sprintf('⏭️  %d utilisateur(s) ignoré(s) (déjà configurés)', $skipped),
            sprintf('📊 Total : %d utilisateur(s)', count($users))
        ]);

        $io->note([
            'Vous pouvez maintenant accéder à l\'outil d\'analyse IA :',
            '👉 Sidebar → "Analyse IA"',
            '👉 URL : /admin/ai-analytics'
        ]);

        return Command::SUCCESS;
    }
}
