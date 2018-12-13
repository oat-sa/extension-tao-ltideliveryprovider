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

namespace oat\ltiDeliveryProvider\test\unit\model\requestLog\rds;

use oat\generis\model\data\Model;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LtiAssignment;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use Psr\Log\LoggerInterface;

/**
 * Class LtiAssignmentAuthorizationServiceTest
 * @package oat\ltiDeliveryProvider\test\unit\model\requestLog\rds
 */
class LtiAssignmentTest extends TestCase
{
    /** @var LtiAssignment */
    private $object;

    /** @var SessionService|\PHPUnit_Framework_MockObject_MockObject */
    private $sessionServiceMock;

    /** @var AttemptServiceInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $attemptServiceMock;

    /** @var User|\PHPUnit_Framework_MockObject_MockObject */
    private $userMock;

    /** @var \core_kernel_classes_Resource|\PHPUnit_Framework_MockObject_MockObject */
    private $deliveryMock;

    /** @var \common_session_Session|\PHPUnit_Framework_MockObject_MockObject */
    private $sessionMock;

    /** @var LtiLaunchData|\PHPUnit_Framework_MockObject_MockObject */
    private $launchDataMock;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sessionServiceMock = $this->createMock(SessionService::class);
        $this->attemptServiceMock = $this->createMock(AttemptServiceInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->deliveryMock = $this->createMock(\core_kernel_classes_Resource::class);

        $this->sessionMock = $this->createMock(TaoLtiSession::class);
        $this->launchDataMock = $this->createMock(LtiLaunchData::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $servileLocatorMock = $this->getServiceLocatorMock([
            SessionService::SERVICE_ID => $this->sessionServiceMock,
            AttemptServiceInterface::SERVICE_ID => $this->attemptServiceMock
        ]);

        $modelMock = $this->createMock(Ontology::class);
        $modelMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->deliveryMock);
        $modelMock->expects($this->once())
            ->method('getProperty')
            ->willReturn(new \core_kernel_classes_Property('PROP'));

        $this->object = new LtiAssignment([]);
        $this->object->setServiceLocator($servileLocatorMock);
        $this->object->setModel($modelMock);
        $this->object->setLogger($this->loggerMock);
    }

    /**
     * Test isDeliveryExecutionAllowed with LTI session with invalid (not numeric) max attempts value.
     */
    public function testIsDeliveryExecutionAllowedNotNumericLtiMaxAttemptsLtiParameter()
    {
        $this->expectException(LtiException::class);

        $this->sessionServiceMock->expects($this->once())
            ->method('getCurrentSession')
            ->willReturn($this->sessionMock);

        $this->sessionMock->expects($this->once())
            ->method('getLaunchData')
            ->willReturn($this->launchDataMock);

        $this->launchDataMock->expects($this->once())
            ->method('hasVariable')
            ->with('custom_max_attempts')
            ->willReturn(true);

        $this->launchDataMock->expects($this->once())
            ->method('getVariable')
            ->with('custom_max_attempts')
            ->willReturn('INVALID_VALUE');

        $this->object->isDeliveryExecutionAllowed('URI', $this->userMock);
    }

    /**
     * Test isDeliveryExecutionAllowed with LTI session with correct max attempts value, execution allowed.
     */
    public function testIsDeliveryExecutionAllowedCorrectMaxAttemptsLtiParameter()
    {
        $this->sessionServiceMock->expects($this->once())
            ->method('getCurrentSession')
            ->willReturn($this->sessionMock);

        $this->sessionMock->expects($this->once())
            ->method('getLaunchData')
            ->willReturn($this->launchDataMock);

        $this->launchDataMock->expects($this->once())
            ->method('hasVariable')
            ->with('custom_max_attempts')
            ->willReturn(true);

        $this->launchDataMock->expects($this->once())
            ->method('getVariable')
            ->with('custom_max_attempts')
            ->willReturn(2);

        $userTokens = [1]; // Amount must be higher than max allowed executions.
        $this->attemptServiceMock->expects($this->once())
            ->method('getAttempts')
            ->willReturn($userTokens);

        $result = $this->object->isDeliveryExecutionAllowed('URI', $this->userMock);

        $this->assertTrue($result, 'Delivery execution must be allowed when user did less attempts than allowed by LTI custom parameter');
    }

    /**
     * Test isDeliveryExecutionAllowed with more execution attempts than allowed.
     */
    public function testIsDeliveryExecutionAllowedAttemptsLimitReached()
    {
        $this->expectException(LtiException::class);

        $this->deliveryMock->expects($this->once())
            ->method('getOnePropertyValue')
            ->willReturn(new \core_kernel_classes_Literal('2'));

        $userTokens = [1, 2, 3, 4]; // Amount must be higher than max allowed executions.
        $this->attemptServiceMock->expects($this->once())
            ->method('getAttempts')
            ->willReturn($userTokens);

        $this->object->isDeliveryExecutionAllowed('URI', $this->userMock);
    }

    /**
     * Test isDeliveryExecutionAllowed with less execution attempts than allowed.
     */
    public function testIsDeliveryExecutionAllowedReturnsTrue()
    {
        $this->deliveryMock->expects($this->once())
            ->method('getOnePropertyValue')
            ->willReturn(new \core_kernel_classes_Literal('2'));

        $userTokens = [1]; // Amount must be higher than max allowed executions.
        $this->attemptServiceMock->expects($this->once())
            ->method('getAttempts')
            ->willReturn($userTokens);

        $result = $this->object->isDeliveryExecutionAllowed('URI', $this->userMock);

        $this->assertTrue($result, 'Delivery execution must be allowed when user did less attempts than allowed');
    }
}

