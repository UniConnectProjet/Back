<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor as ExecutorORMExecutor;
use Doctrine\Common\DataFixtures\Loader as DataFixturesLoader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger as PurgerORMPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:fixtures:load')]
class LoadFixturesCommand extends Command
{
    public function __construct(
    private EntityManagerInterface $em,
    private DataFixturesLoader $loader
    ) {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Charge les fixtures avec un mode (light ou full)')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'light|full', 'light');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getOption('mode');

        putenv("FIXTURE_MODE=$mode");
        $output->writeln("<info>Chargement des fixtures en mode: $mode</info>");

        $fixtures = $this->loader->getFixtures();
        $purger = new PurgerORMPurger($this->em);
        $executor = new ExecutorORMExecutor($this->em, $purger);

        $executor->purge();
        $executor->execute($fixtures);

        $output->writeln('<info>Fixtures chargées avec succès.</info>');
        return Command::SUCCESS;
    }
}