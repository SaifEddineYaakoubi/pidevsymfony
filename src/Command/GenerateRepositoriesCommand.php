<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GenerateRepositoriesCommand extends Command
{
    protected static $defaultName = 'app:generate:Repositories';

    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        parent::__construct();

        // Finder is not registered as a container service by default; instantiate it when needed.
        $this->filesystem = new Filesystem();
        $this->projectDir = rtrim($projectDir, DIRECTORY_SEPARATOR);
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates repository classes for all entities.')
            ->setHelp('This command will generate repository classes for all entities in src/Entity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generating repositories for all entities...');

        $entityDir = $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $repositoryDir = $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Repository';

        if (!$this->filesystem->exists($entityDir)) {
            $output->writeln(sprintf('<error>Entity directory not found: %s</error>', $entityDir));
            return Command::FAILURE;
        }

        if (!$this->filesystem->exists($repositoryDir)) {
            $this->filesystem->mkdir($repositoryDir);
        }

        // Use a fresh Finder instance for every run.
        $finder = new Finder();
        $finder->files()->in($entityDir)->name('*.php'); // Look for PHP files in the Entity directory

        foreach ($finder as $file) {
            $entityClass = $file->getBasename('.php');
            $repositoryClass = 'App\\Repository\\' . $entityClass . 'Repository';

            // Create the repository class file
            $repositoryCode = <<<PHP
<?php

namespace App\Repository;

use App\Entity\\$entityClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class {$entityClass}Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, $entityClass::class);
    }

    // Add custom methods as needed
}
PHP;

            // Define the repository file path
            $repositoryPath = $repositoryDir . DIRECTORY_SEPARATOR . $entityClass . 'Repository.php';

            // Only generate if the repository does not already exist
            if (!$this->filesystem->exists($repositoryPath)) {
                $this->filesystem->dumpFile($repositoryPath, $repositoryCode);
                $output->writeln("Generated repository: $repositoryClass");
            } else {
                $output->writeln("Repository already exists for: $entityClass");
            }
        }

        $output->writeln('Repository generation complete!');
        return Command::SUCCESS;
    }
}
