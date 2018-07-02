<?php

declare(strict_types=1);

/**
 * @author Patrick Jahns <github@patrickjahns.de>
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
 */

namespace OC\Tekton\Console\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class TektonConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     * @psalm-suppress PossiblyUndefinedMethod
	 * @suppress PhanUndeclaredMethod
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tekton');
        /**
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $rootNode
            ->children()
                ->arrayNode('build_tasks')
                    ->info('tasks to be execute before packaging')
                    ->requiresAtLeastOneElement()->scalarPrototype()->end()
                ->end()
                ->arrayNode('package')
                    ->info('configuration options related to packaging')
                    ->children()
                        ->booleanNode('flatten')
                            ->defaultTrue()
                            ->info('defines if per default symlinks should be flattened and resolved as files')
                        ->end()
                        ->arrayNode('include')
                            ->requiresAtLeastOneElement()->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('exclude')
                            ->requiresAtLeastOneElement()->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
