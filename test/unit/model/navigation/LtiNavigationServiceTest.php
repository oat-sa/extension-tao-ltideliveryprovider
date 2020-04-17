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

use oat\ltiDeliveryProvider\model\navigation\DefaultMessageFactory;
use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\generis\test\MockObject;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use Psr\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class LtiNavigationServiceTest
 * @package oat\ltiDeliveryProvider\test\unit\model\navigation
 */
class LtiNavigationServiceTest extends TestCase
{
    private const THANK_YOU_URL = 'http://THANK_YOU/';

    private const DELIVERY_EXECUTION_ID = 'FAKE_DELIVERY_EXECUTION_ID';

    /**
     * @var LtiNavigationService|MockObject
     */
    private $object;

    /**
     * @var LtiLaunchData|MockObject
     */
    private $launchDataMock;

    /**
     * @var DeliveryExecutionInterface|MockObject
     */
    private $deliveryExecutionMock;

    /**
     * @var DefaultMessageFactory|MockObject
     */
    private $messageFactoryMock;

    /**
     * @var UrlHelper|MockObject
     */
    private $urlHelperMock;

    /**
     * @var LtiMessage|MockObject
     */
    private $ltiMessageMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock method call parameters
        $this->launchDataMock = $this->createMock(LtiLaunchData::class);

        $this->deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);
        $this->deliveryExecutionMock->method('getIdentifier')
            ->willReturn(self::DELIVERY_EXECUTION_ID);

        $this->messageFactoryMock = $this->getMessageFactoryMock();

        $this->object = new LtiNavigationService([
            LtiNavigationService::OPTION_THANK_YOU_SCREEN => false,
            LtiNavigationService::OPTION_DELIVERY_RETURN_STATUS => false,
            LtiNavigationService::OPTION_MESSAGE_FACTORY => $this->messageFactoryMock
        ]);

        $serviceLocatorMock = $this->createServiceLocatorMock();
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    /**
     * Test getReturnUrl without return url in launch data.
     */
    public function testGetReturnUrlNoReturnUrl()
    {
        $this->launchDataMock->expects($this->once())
            ->method('hasReturnUrl')
            ->willReturn(false);

        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame(self::THANK_YOU_URL, $result, 'Returned url must be as expected for request without return url.');
    }

    /**
     * @param bool $thankYouScreenOption
     * @param string $ltiReturnUrl
     * @param string $expectedUrl
     *
     * @dataProvider dataProviderTestGetReturnUrl_NoSkipThankYouParameter
     */
    public function testGetReturnUrl_NoSkipThankYouParameter(bool $thankYouScreenOption, string $ltiReturnUrl, string $expectedUrl): void
    {
        $this->launchDataMock->method('hasReturnUrl')
            ->willReturn(true);
        $this->launchDataMock->method('getReturnUrl')
            ->willReturn($ltiReturnUrl);

        $this->launchDataMock->method('hasVariable')
            ->with('custom_skip_thankyou')
            ->willReturn(false);

        $this->object->setOption('thankyouScreen', $thankYouScreenOption);
        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame($expectedUrl, $result, 'Method must return correct "return url"');
    }

    public function testGetReturnUrl_SkipThankYouParameter_False(): void
    {
        $this->assertTrue(false);
    }

    public function testGetReturnUrl_SkipThankYouParameter_True(): void
    {
        $this->assertTrue(false);
    }

    public function testGetReturnUrl_SkipThankYouParameter_InvalidType(): void
    {
        $this->assertTrue(false);
    }

    public function dataProviderTestGetReturnUrl_NoSkipThankYouParameter(): array
    {
        return [
            'Option show thank you screen true' => [
                'thankYouScreenOption' => true,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL',
                'expectedUrl' => self::THANK_YOU_URL
            ],
            'Option show thank you screen false' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL?deliveryExecution=' . self::DELIVERY_EXECUTION_ID
            ]
        ];
    }

    /**
     * @return ServiceLocatorInterface|MockObject
     */
    private function createServiceLocatorMock(): ServiceLocatorInterface
    {
        $this->urlHelperMock = $this->createMock(UrlHelper::class);
        $this->urlHelperMock->method('buildUrl')
            ->willReturn(self::THANK_YOU_URL);

        $loggerMock = $this->createMock(LoggerInterface::class);

        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                UrlHelper::class => $this->urlHelperMock,
                LoggerService::SERVICE_ID => $loggerMock
            ]
        );
        return $serviceLocatorMock;
    }

    private function getMessageFactoryMock(): DefaultMessageFactory
    {
        $this->ltiMessageMock = $this->createMock(LtiMessage::class);
        $this->ltiMessageMock->method('getUrlParams')
            ->willReturn([]);

        $messageFactoryMock = $this->createMock(DefaultMessageFactory::class);
        $messageFactoryMock->method('getLtiMessage')
            ->willReturn($this->ltiMessageMock);

        return $messageFactoryMock;
    }
}
