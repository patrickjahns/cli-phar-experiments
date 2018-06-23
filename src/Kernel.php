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

namespace Cliph;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Cliph Kernel is a simplified Entrypoint for the application
 * @package Cliph
 */
class Kernel
{
	/** @var ContainerBuilder */
	private $container;

	/**
	 * @return ContainerBuilder
	 * @throws \RuntimeException
	 */
	public function getContainer(): ContainerBuilder
	{
		if ($this->container === null) {
			throw new \RuntimeException("kernel not initialized");
		}
		return $this->container;
	}

	/**
	 * @throws \Exception
	 */
	public function boot(): void
	{
		if ($this->container !== null) {
			return;
		}
		$builder = new ContainerBuilder();
		$loader = new YamlFileLoader($builder, new FileLocator(__DIR__.'/../config'));
		$loader->load('services.yml');
		$builder->compile();
		$this->container = $builder;
	}

}
