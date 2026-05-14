<?php
// src/Command/CreateAdminUserCommand.php
namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create or promote a user to admin'
)]
class CreateAdminUserCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $em, 
        private UtilisateurRepository $repo,
        private UserPasswordHasherInterface $passwordHasher
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the user (will be hashed). If omitted, a random password is generated.')
            ->addArgument('role', InputArgument::OPTIONAL, 'Role of the user (admin, responsable_stock, agriculteur)', 'admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password') ?: bin2hex(random_bytes(6));
        $role = $input->getArgument('role') ?: 'admin';

        $user = $this->repo->findOneByEmail($email);

        if (!$user) {
            $user = new Utilisateur();
            // set minimal required fields
            $user->setEmail($email);
            $user->setNom(ucfirst($role));
            $user->setPrenom('User');
            $user->setDateCreation(new \DateTime());
            $user->setStatut(true);
        }

        // Use Symfony's password hasher instead of PHP's password_hash
        $hashed = $this->passwordHasher->hashPassword($user, $password);
        $user->setMotDePasse($hashed);
        $user->setRole($role);

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User %s with role %s created. Password: %s', $email, $role, $password));

        return Command::SUCCESS;
    }
}

