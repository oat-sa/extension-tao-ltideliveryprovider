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

namespace oat\ltiDeliveryProvider\tests\model;

use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LtiDeliveryFactory;
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\taoLti\models\classes\LtiLaunchData;

class LtiLaunchDataServiceTest extends TestCase
{
    public function testFindDeliveryFromLaunchData()
    {
        $service = $this->getLtiLaunchDataService([
            LtiDeliveryFactory::SERVICE_ID => $this->getLtiDeliveryFactoryMock()
        ]);

        $data = new LtiLaunchData([], []);

        $delivery = $service->findDeliveryFromLaunchData($data);

        $this->assertTrue($delivery);
    }

    public function testFindDeliveryExecutionFromLaunchData()
    {
        $service = $this->getLtiLaunchDataService([
            LtiDeliveryFactory::SERVICE_ID => $this->getLtiDeliveryFactoryMock()
        ]);

        $data = new LtiLaunchData([], []);

        $this->assertTrue($service->findDeliveryExecutionFromLaunchData($data));
    }

    private function getLtiDeliveryFactoryMock()
    {
        $factory = $this->getMockBuilder(LtiDeliveryFactory::class)->getMock();
        $factory->method('create')->willReturn(true);

        return $factory;
    }

    /**
     * @param array $services
     *
     * @return LtiLaunchDataService
     */
    private function getLtiLaunchDataService(array $services)
    {
        $serviceLocatorMock = $this->getServiceLocatorMock($services);

        $service = new LtiLaunchDataService();
        $service->setServiceLocator($serviceLocatorMock);

        return $service;
    }
}
