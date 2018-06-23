<?php

declare(strict_types=1);

namespace Cliph\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloWorldCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('hello')
            ->setDescription('say hello world');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $output->writeln('<fg=cyan;options=bold>Hello World</>');

        return 0;
    }
}
