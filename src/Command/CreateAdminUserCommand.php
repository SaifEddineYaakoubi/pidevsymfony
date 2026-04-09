<?php
// src/Command/CreateAdminUserCommand.php
namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateAdminUserCommand extends Command
{
    protected static $defaultName = 'app:create-admin';
    protected static $defaultDescription = 'Create or promote a user to admin';

    public function __construct(private EntityManagerInterface $em, private UtilisateurRepository $repo)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password for the user (will be hashed). If omitted, a random password is generated.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password') ?: bin2hex(random_bytes(6));

        $user = $this->repo->findOneByEmail($email);

        if (!$user) {
            $user = new Utilisateur();
            // set minimal required fields
            $user->setEmail($email);
            $user->setNom('Admin');
            $user->setPrenom('User');
            $user->setDateCreation(new \DateTime());
            $user->setStatut(true);
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $user->setMotDePasse($hashed);
        $user->setRole('admin');

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User %s is now an admin. Password: %s', $email, $password));

        return Command::SUCCESS;
    }
}

