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

namespace OC\Tekton\Tests;

use Cliph\Kernel;
use PHPUnit\Framework\TestCase;

abstract class KernelTestCase extends TestCase
{
    /**
     * @return Kernel
     */
    protected static function createKernel()
    {
        return new Kernel();
    }

    protected static function bootKernel()
    {
        $kernel = self::createKernel();
        $kernel->boot();

        return $kernel;
    }
}
