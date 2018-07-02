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

use Cliph\Console\Configuration\TektonConfiguration;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurableCommand extends Command
{

	protected function configure() {
		$this->setName('configurable')
			->setDescription('example command that uses symfony configuration')
			->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'confiugration file', null);
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$config = $input->getOption('config');
		$parsedConfig = Yaml::parse(file_get_contents($config));
		$processor = new Processor();
		$configurationDefinition = new TektonConfiguration();
		$processedConfig = $processor->processConfiguration($configurationDefinition, [$parsedConfig]);
		var_dump($processedConfig);
		$dumper = new YamlReferenceDumper();
		var_dump($dumper->dump($configurationDefinition));
	}
}
