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

use core_kernel_classes_Literal as KernelLiteralProperty;
use core_kernel_classes_Property as KernelProperty;
use core_kernel_classes_Resource as KernelResource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\LtiAssignment;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoLti\models\classes\LtiClientException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use Psr\Log\LoggerInterface;
use oat\generis\test\MockObject;

/**
 * Class LtiAssignmentAuthorizationServiceTest
 * @package oat\ltiDeliveryProvider\test\unit\model\requestLog\rds
 */
class LtiAssignmentTest extends TestCase
{
    /** @var LtiAssignment */
    private $object;

    /** @var SessionService|MockObject */
    private $sessionServiceMock;

    /** @var AttemptServiceInterface|MockObject */
    private $attemptServiceMock;

    /** @var User|MockObject */
    private $userMock;

    /** @var KernelResource|MockObject */
    private $deliveryMock;

    /** @var \common_session_Session|MockObject */
    private $sessionMock;

    /** @var LtiLaunchData|MockObject */
    private $launchDataMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var string[] */
    private $deliveryProperties;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionServiceMock = $this->createMock(SessionService::class);
        $this->attemptServiceMock = $this->createMock(AttemptServiceInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->deliveryMock = $this->createMock(KernelResource::class);

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
        $modelMock
            ->method('getProperty')
            ->willReturnCallback(static function (string $uri): KernelProperty {
                return new KernelProperty($uri);
            });

        $this->deliveryMock
            ->method('getOnePropertyValue')
            ->willReturnCallback(function (KernelProperty $property): ?KernelLiteralProperty {
                return current($this->getDeliveryProperties([$property]));
            });
        $this->deliveryMock
            ->method('getPropertiesValues')
            ->willReturnCallback([$this, 'getDeliveryProperties']);

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
        $this->expectException(LtiClientException::class);

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
        $this->expectException(LtiClientException::class);

        $this->deliveryProperties = [
            DeliveryContainerService::PROPERTY_MAX_EXEC => 2
        ];

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
        $this->deliveryProperties = [
            DeliveryContainerService::PROPERTY_MAX_EXEC => 2
        ];

        $userTokens = [1]; // Amount must be higher than max allowed executions.
        $this->attemptServiceMock->expects($this->once())
            ->method('getAttempts')
            ->willReturn($userTokens);

        $result = $this->object->isDeliveryExecutionAllowed('URI', $this->userMock);

        $this->assertTrue($result, 'Delivery execution must be allowed when user did less attempts than allowed');
    }

    /**
     * @param KernelProperty[] $properties
     *
     * @return KernelLiteralProperty[]
     */
    public function getDeliveryProperties(array $properties): array
    {
        return array_map(function (KernelProperty $property): ?KernelLiteralProperty {
            return isset($this->deliveryProperties[$property->getUri()])
                ? new KernelLiteralProperty($this->deliveryProperties[$property->getUri()])
                : null;
        }, $properties);
    }
}
