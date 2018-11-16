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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\ltiDeliveryProvider\test\unit\model\navigation;

use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;

/**
 * Class LtiNavigationServiceTest
 * @package oat\ltiDeliveryProvider\test\unit\model\navigation
 */
class LtiNavigationServiceTest extends TestCase
{
    /**
     * @var LtiNavigationService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $object;

    /**
     * @var LtiLaunchData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $launchDataMock;

    /**
     * @var DeliveryExecutionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deliveryExecutionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->object = $this->getMockBuilder(LtiNavigationService::class)
            ->setMethods(['getThankYouUrl'])
            ->getMock();

        $this->launchDataMock = $this->getMockBuilder(LtiLaunchData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryExecutionMock = $this->getMockBuilder(DeliveryExecutionInterface::class)
            ->getMock();
    }

    /**
     * Test getReturnUrl without return url in launch data.
     */
    public function testGetReturnUrlNoReturnUrl()
    {
        $expectedUrl = 'http://THANK_YOU/';

        $this->launchDataMock->expects($this->once())
            ->method('hasReturnUrl')
            ->willReturn(false);
        $this->object->expects($this->once())
            ->method('getThankYouUrl')
            ->willReturn($expectedUrl);

        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        $this->assertEquals($expectedUrl, $result, 'Returned url must be as expected for request without return url.');
    }
}
