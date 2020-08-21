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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\session\DataAccess\Factory;

use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\session\DataAccess\Factory\SessionCookieAttributesFactory;
use oat\tao\model\security\Business\Contract\SecuritySettingsRepositoryInterface;
use oat\tao\model\security\Business\Domain\Setting;
use oat\tao\model\security\Business\Domain\SettingsCollection;
use oat\tao\model\security\DataAccess\Repository\SecuritySettingsRepository;
use oat\tao\model\session\Business\Contract\SessionCookieAttributesFactoryInterface;
use oat\tao\model\session\Business\Domain\SessionCookieAttributeCollection;
use oat\tao\model\session\Business\Domain\SessionCookiePathAttribute;
use oat\taoLti\models\classes\LtiLaunchData;

class SessionCookieAttributesFactoryTest extends TestCase
{
    private const DEFAULT_ATTRIBUTES_STRING = '/';

    /** @var SessionCookieAttributesFactory */
    private $sut;

    /** @var string[] */
    private $ltiVariables = [];

    /** @var string */
    private $contentSecurityPolicy = '';

    /**
     * @before
     */
    public function init(): void
    {
        $this->sut = $this->getMockBuilder(SessionCookieAttributesFactory::class)
            ->setConstructorArgs(
                [
                    $this->createBaseSessionCookieAttributesFactoryMock(),
                    $this->createSecuritySettingsRepositoryMock(),
                ]
            )
            ->onlyMethods(['createLtiLaunchData'])
            ->getMock();

        $this->sut
            ->expects(static::once())
            ->method('createLtiLaunchData')
            ->willReturnCallback([$this, 'createLtiLaunchData']);
    }

    public function createLtiLaunchData(): LtiLaunchData
    {
        return new LtiLaunchData($this->ltiVariables, []);
    }

    public function createSettingsCollection(): SettingsCollection
    {
        return new SettingsCollection(
            new Setting(SecuritySettingsRepository::CONTENT_SECURITY_POLICY, $this->contentSecurityPolicy)
        );
    }

    public function testCreateForLtiLaunchFromAnyDomain(): void
    {
        $this->expectLtiLaunchRequest();
        $this->expectAnyThirdPartyDomain();

        self::assertContainsSameSiteNone(
            $this->sut->create()
        );
    }

    public function testCreateForLtiLaunchFromThirdPartyDomain(): void
    {
        $this->expectLtiLaunchRequest();
        $this->expectThirdPartyDomain();

        self::assertContainsSameSiteNone(
            $this->sut->create()
        );
    }

    public function testCreateForLtiLaunchFromOwnDomain(): void
    {
        $this->expectLtiLaunchRequest();

        self::assertContainsNoSameSiteNone(
            $this->sut->create()
        );
    }

    public function testCreateForRegularRequest(): void
    {
        self::assertContainsNoSameSiteNone(
            $this->sut->create()
        );
    }

    private static function assertContainsSameSiteNone(SessionCookieAttributeCollection $sessionCookieAttributes): void
    {
        static::assertEquals(
            implode('; ', [self::DEFAULT_ATTRIBUTES_STRING, 'samesite=none']),
            $sessionCookieAttributes
        );
    }

    private static function assertContainsNoSameSiteNone(
        SessionCookieAttributeCollection $sessionCookieAttributes
    ): void {
        static::assertEquals(self::DEFAULT_ATTRIBUTES_STRING, $sessionCookieAttributes);
    }

    private function createBaseSessionCookieAttributesFactoryMock(): SessionCookieAttributesFactoryInterface
    {
        $baseSessionAttributesFactoryMock = $this->createMock(SessionCookieAttributesFactoryInterface::class);

        $baseSessionAttributesFactoryMock
            ->expects(static::once())
            ->method('create')
            ->willReturn(
                (new SessionCookieAttributeCollection())
                    ->add(new SessionCookiePathAttribute(self::DEFAULT_ATTRIBUTES_STRING))
            );

        return $baseSessionAttributesFactoryMock;
    }

    private function createSecuritySettingsRepositoryMock(): SecuritySettingsRepositoryInterface
    {
        $securitySettingsRepositoryMock = $this->createMock(SecuritySettingsRepositoryInterface::class);
        $securitySettingsRepositoryMock
            ->method('findAll')
            ->willReturnCallback([$this, 'createSettingsCollection']);

        return $securitySettingsRepositoryMock;
    }

    private function expectLtiLaunchRequest(): void
    {
        $this->ltiVariables[LtiLaunchData::LTI_VERSION] = 'LTI-1p3';
    }

    private function expectAnyThirdPartyDomain(): void
    {
        $this->contentSecurityPolicy = '*';
    }

    private function expectThirdPartyDomain(): void
    {
        $this->contentSecurityPolicy = 'list';
    }
}
