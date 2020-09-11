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
 * Copyright (c) 2019-2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\model;

use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LtiDeliveryFactory;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\ltiDeliveryProvider\model\LTIDeliveryToolFactory;

class LtiDeliveryFactoryTest extends TestCase
{
    public function testCreateFromLtiSession(): void
    {
        $tool = $this->getMockBuilder(LTIDeliveryTool::class)->disableOriginalConstructor()->getMock();
        $tool->expects($this->once())->method('getDeliveryFromLink')->willReturn(true);

        $toolFactory = $this->createMock(LTIDeliveryToolFactory::class);
        $toolFactory->expects($this->once())->method('create')->willReturn($tool);

        $serviceLocator = $this->getServiceLocatorMock(
            [
                LTIDeliveryToolFactory::SERVICE_ID => $toolFactory
            ]
        );

        $factory = new LtiDeliveryFactory();
        $factory->setServiceLocator($serviceLocator);

        $result = $factory->create(null);

        $this->assertTrue($result);
    }
}
