<?php

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 *
 * @copyright Copyright (c) 2018, Patrick Jahns.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Cliph\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class TarCommand extends Command
{
	protected function configure(): void
	{
		$this->setName('tar')
			->addArgument('src', InputArgument::REQUIRED, 'source directory')
			->addArgument('output', InputArgument::REQUIRED, 'output file')
			->setDescription('create a tar file for a folder');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$srcDirectory = $input->getArgument('src');
		$targetFile = $input->getArgument('output');
		$finder = new Finder();
		$finder->in($srcDirectory);
		$archive = new \Archive_Tar($targetFile, true);
		$archive->createModify($srcDirectory, '', './tests/data/');


	}
}
