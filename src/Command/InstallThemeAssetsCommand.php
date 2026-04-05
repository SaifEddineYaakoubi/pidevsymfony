<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:install-theme-assets',
    description: 'Copies AdminLTE and AgriCulture static assets into public/ so Symfony can serve them.'
)]
final class InstallThemeAssetsCommand extends Command
{
    private Filesystem $fs;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
        $this->fs = new Filesystem();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $srcAdmin = $this->projectDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'AdminLTE' . DIRECTORY_SEPARATOR . 'AdminLTE-3.1.0';
        $srcFront = $this->projectDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'front' . DIRECTORY_SEPARATOR . 'AgriCulture' . DIRECTORY_SEPARATOR . 'AgriCulture-1.0.0';

        $dstAdmin = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adminlte';
        $dstFront = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'agriculture';

        if (!$this->fs->exists($srcAdmin)) {
            $output->writeln(sprintf('<error>Source AdminLTE not found: %s</error>', $srcAdmin));
            return Command::FAILURE;
        }
        if (!$this->fs->exists($srcFront)) {
            $output->writeln(sprintf('<error>Source AgriCulture not found: %s</error>', $srcFront));
            return Command::FAILURE;
        }

        $output->writeln('<info>Copying AdminLTE assets…</info>');
        $this->mirrorDir($srcAdmin, $dstAdmin);
        $output->writeln(sprintf('<info>AdminLTE installed to %s</info>', $dstAdmin));

        $output->writeln('<info>Copying AgriCulture assets…</info>');
        $this->mirrorDir($srcFront, $dstFront);
        $output->writeln(sprintf('<info>AgriCulture installed to %s</info>', $dstFront));

        $output->writeln('<info>Done.</info>');
        return Command::SUCCESS;
    }

    private function mirrorDir(string $source, string $target): void
    {
        $this->fs->mkdir($target);

        $finder = new Finder();
        $finder->in($source)->ignoreDotFiles(false);

        foreach ($finder as $file) {
            $relative = $file->getRelativePathname();
            $dstPath = $target . DIRECTORY_SEPARATOR . $relative;

            if ($file->isDir()) {
                $this->fs->mkdir($dstPath);
                continue;
            }

            $this->fs->mkdir(dirname($dstPath));
            $this->fs->copy($file->getRealPath(), $dstPath, true);
        }
    }
}

