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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\requestLog\rds;

use common_session_Session;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\oatbox\session\SessionService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;

class LTIDeliveryToolTest extends TestCase
{
    /** @var LTIDeliveryTool */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new LTIDeliveryTool();
    }

    public function testGetFinishUrlNotLtiSessionThrowsException(): void
    {
        self::expectException(LtiException::class);

        $ltiNavigationServiceMock = $this->createMock(LtiNavigationService::class);
        $sessionServiceMock = $this->createMock(SessionService::class);
        $sessionServiceMock->method('getCurrentSession')
            ->willReturn($this->createMock(common_session_Session::class));

        $serviceLocatorMock = $this->getServiceLocatorMock([
            LtiNavigationService::SERVICE_ID => $ltiNavigationServiceMock,
            SessionService::SERVICE_ID => $sessionServiceMock
        ]);
        $this->subject->setServiceLocator($serviceLocatorMock);

        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $this->subject->getFinishUrl($deliveryExecutionMock);
    }

    public function testGetFnishUrlReturnsCorrectUrl(): void
    {
        $expectedUrl = 'http://www.FAKE_RETURN_URL.com/';

        $ltiNavigationServiceMock = $this->createMock(LtiNavigationService::class);
        $ltiNavigationServiceMock->method('getReturnUrl')
            ->willReturn($expectedUrl);

        // Mock LtiLaunchData
        $ltiSessionMock = $this->createMock(TaoLtiSession::class);
        $ltiSessionMock->method('getLaunchData')
            ->willReturn($this->createMock(LtiLaunchData::class));
        $sessionServiceMock = $this->createMock(SessionService::class);
        $sessionServiceMock->method('getCurrentSession')
            ->willReturn($ltiSessionMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            LtiNavigationService::SERVICE_ID => $ltiNavigationServiceMock,
            SessionService::SERVICE_ID => $sessionServiceMock
        ]);
        $this->subject->setServiceLocator($serviceLocatorMock);

        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $finishUrl = $this->subject->getFinishUrl($deliveryExecutionMock);

        self::assertSame($expectedUrl, $finishUrl, "Method must return correct finish url");
    }
}

