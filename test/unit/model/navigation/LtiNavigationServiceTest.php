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

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\navigation;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\navigation\DefaultMessageFactory;
use oat\ltiDeliveryProvider\model\navigation\LtiMessageFactoryInterface;
use oat\ltiDeliveryProvider\model\navigation\LtiNavigationService;
use oat\oatbox\log\LoggerService;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use Psr\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class LtiNavigationServiceTest
 *
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
     * @var LtiMessageFactoryInterface|MockObject
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
            LtiNavigationService::OPTION_MESSAGE_FACTORY => $this->messageFactoryMock,
        ]);

        $serviceLocatorMock = $this->createServiceLocatorMock();
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    /**
     * Test getReturnUrl without return url in launch data.
     */
    public function testGetReturnUrlNoLtiReturnUrlParameter(): void
    {
        $this->launchDataMock->expects($this->once())
            ->method('hasReturnUrl')
            ->willReturn(false);

        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame(self::THANK_YOU_URL, $result, 'Returned url must be as expected for request without return url.');
    }

    /**
     * @param bool $thankYouScreenOption
     * @param string $expectedUrl
     *
     * @dataProvider dataProviderTestGetReturnUrlNoSkipThankYouParameter
     */
    public function testGetReturnUrlNoSkipThankYouParameter(
        bool $thankYouScreenOption,
        string $ltiReturnUrl,
        string $expectedUrl
    ): void {
        $this->mockLtiReturnUrlParameter($ltiReturnUrl);

        $this->launchDataMock->method('hasVariable')
            ->with('custom_skip_thankyou')
            ->willReturn(false);

        $this->object->setOption('thankyouScreen', $thankYouScreenOption);
        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame($expectedUrl, $result, 'Method must return correct "return url"');
    }

    /**
     * @param string $skipThankYou
     * @param string $expectedUrl
     *
     * @dataProvider dataProviderTestGetReturnUrlSkipThankYouParameterValidValue
     */
    public function testGetReturnUrlSkipThankYouParameterValidValue(string $skipThankYou, string $expectedUrl): void
    {
        $this->mockLtiReturnUrlParameter();

        $this->launchDataMock->method('hasVariable')
            ->with('custom_skip_thankyou')
            ->willReturn(true);
        $this->launchDataMock->method('getVariable')
            ->with('custom_skip_thankyou')
            ->willReturn($skipThankYou);

        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame($expectedUrl, $result, 'Method must return correct "return url"');
    }

    /**
     * @param bool $thankYouScreenOption
     * @param string $expectedUrl
     *
     * @dataProvider dataProviderTestGetReturnUrlSkipThankYouParameterInvalidType
     */
    public function testGetReturnUrlSkipThankYouParameterInvalidType(
        bool $thankYouScreenOption,
        string $expectedUrl
    ): void {
        $this->mockLtiReturnUrlParameter();

        $skipThankYouLtiParameter = 'INVALID_VALUE';
        $this->launchDataMock->method('hasVariable')
            ->with('custom_skip_thankyou')
            ->willReturn(false);
        $this->launchDataMock->method('getVariable')
            ->with('custom_skip_thankyou')
            ->willReturn($skipThankYouLtiParameter);

        $this->object->setOption('thankyouScreen', $thankYouScreenOption);
        $result = $this->object->getReturnUrl($this->launchDataMock, $this->deliveryExecutionMock);

        static::assertSame($expectedUrl, $result, 'Method must return correct "return url"');
    }

    public function dataProviderTestGetReturnUrlNoSkipThankYouParameter(): array
    {
        return [
            'Option show thank you screen true' => [
                'thankYouScreenOption' => true,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL',
                'expectedUrl' => self::THANK_YOU_URL,
            ],
            'Option show thank you screen false' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
            'Option show thank you screen false, LTIReturnUrl with port' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL:1234',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL:1234?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
            'Option show thank you screen false, LTIReturnUrl with path' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL/PATH',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL/PATH?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
            'Option show thank you screen false, LTIReturnUrl with path and port' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL:1234/PATH',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL:1234/PATH?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
            'Option show thank you screen false, LTIReturnUrl has query parameter' => [
                'thankYouScreenOption' => false,
                'ltiReturnUrl' => 'http://FAKE_LTI_RETURN.URL?lti_param1=lti_value1',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL?lti_param1=lti_value1&deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
        ];
    }

    public function dataProviderTestGetReturnUrlSkipThankYouParameterValidValue(): array
    {
        return [
            'Option skip thank you screen true' => [
                'skipThankYou' => 'true',
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
            'Option skip thank you screen false' => [
                'skipThankYou' => 'false',
                'expectedUrl' => self::THANK_YOU_URL,
            ],
        ];
    }

    public function dataProviderTestGetReturnUrlSkipThankYouParameterInvalidType(): array
    {
        return [
            'Option show thank you screen true' => [
                'thankYouScreenOption' => true,
                'expectedUrl' => self::THANK_YOU_URL,
            ],
            'Option show thank you screen false' => [
                'thankYouScreenOption' => false,
                'expectedUrl' => 'http://FAKE_LTI_RETURN.URL?deliveryExecution=' . self::DELIVERY_EXECUTION_ID,
            ],
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

        return $this->getServiceLocatorMock(
            [
                UrlHelper::class => $this->urlHelperMock,
                LoggerService::SERVICE_ID => $loggerMock,
            ]
        );
    }

    private function getMessageFactoryMock(): LtiMessageFactoryInterface
    {
        $this->ltiMessageMock = $this->createMock(LtiMessage::class);
        $this->ltiMessageMock->method('getUrlParams')
            ->willReturn([]);

        $messageFactoryMock = $this->createMock(DefaultMessageFactory::class);
        $messageFactoryMock->method('getLtiMessage')
            ->willReturn($this->ltiMessageMock);

        return $messageFactoryMock;
    }

    private function mockLtiReturnUrlParameter(string $ltiReturnUrl = 'http://FAKE_LTI_RETURN.URL'): void
    {
        $this->launchDataMock->method('hasReturnUrl')
            ->willReturn(true);
        $this->launchDataMock->method('getReturnUrl')
            ->willReturn($ltiReturnUrl);
    }
}
