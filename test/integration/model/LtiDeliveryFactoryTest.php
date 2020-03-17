<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\test\integration\model;

use core_kernel_classes_Resource;
use oat\ltiDeliveryProvider\model\LtiDeliveryFactory;
use oat\tao\test\TaoPhpUnitTestRunner;

class LtiDeliveryFactoryTest extends TaoPhpUnitTestRunner
{
    protected function setUp(): void
    {
        $this->disableCache();
    }

    public function testCreate()
    {
        $factory = new LtiDeliveryFactory();

        $result = $factory->create('http://url');

        $this->assertInstanceOf(core_kernel_classes_Resource::class, $result);
    }
}
