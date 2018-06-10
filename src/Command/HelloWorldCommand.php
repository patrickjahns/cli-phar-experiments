<?php

namespace Cliph\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloWorldCommand extends Command
{
	protected function configure()
	{
		$this->setName('hello')
			->setDescription('say hello world');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<fg=cyan;options=bold>Hello World</>');
	}

}
