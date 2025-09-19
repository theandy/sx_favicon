<?php
declare(strict_types=1);

namespace AndreasLoewer\SxFavicon\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AndreasLoewer\SxFavicon\Service\GeneratorService;

#[AsCommand(name: 'sxfavicon:generate', description: 'Generate favicons for a site')]
final class GenerateCommand extends Command
{
    public function __construct(private readonly GeneratorService $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('site', InputArgument::REQUIRED, 'Site identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generator->generateForSite((string)$input->getArgument('site'));
        $output->writeln('<info>Favicons generated.</info>');
        return Command::SUCCESS;
    }
}
