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
use oat\ltiDeliveryProvider\model\LtiLaunchDataService;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use RuntimeException;

class LtiLaunchDataServiceTest extends TestCase
{
    /**
     * @throws LtiVariableMissingException
     */
    public function testFindLaunchDataByDeliveryExecutionId()
    {
        $service = $this->getLtiLaunchDataService(
            [
                [
                    'data' => ['some_field' => 'some_data']
                ]
            ]
        );

        $launchData = $service->findLaunchDataByDeliveryExecutionId('http://some/delivery#id');

        $this->assertInstanceOf(LtiLaunchData::class, $launchData);

        $this->assertEquals('some_data', $launchData->getVariable('some_field'));
    }


    public function testFindLaunchDataByUnexistedDeliveryExecutionId()
    {
        $service = $this->getLtiLaunchDataService([]);

        $this->expectException(RuntimeException::class);

        $service->findLaunchDataByDeliveryExecutionId('http://some/delivery#id');
    }

    public function testFindIncompleteLaunchDataByDeliveryExecutionId()
    {
        $service = $this->getLtiLaunchDataService([]);

        $this->expectException(RuntimeException::class);

        $service->findLaunchDataByDeliveryExecutionId('http://some/delivery#id');
    }

    private function getLtiLaunchDataService($returnValue)
    {
        $serviceLocatorMock = $this->getMockedServiceLocator($returnValue);

        $service = new LtiLaunchDataService();
        $service->setServiceLocator($serviceLocatorMock);

        return $service;
    }

    private function getMockedServiceLocator($returnValue)
    {
        $deliveryLogMock = $this->getMockBuilder(DeliveryLog::class)->disableOriginalConstructor()->getMock();
        $deliveryLogMock->method('get')->willReturn($returnValue);

        return $this->getServiceLocatorMock([DeliveryLog::SERVICE_ID => $deliveryLogMock]);
    }
}
