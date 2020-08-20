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

namespace oat\ltiDeliveryProvider\model\session\DataAccess\Factory;

use common_http_Request as Request;
use oat\tao\model\security\Business\Contract\SecuritySettingsRepositoryInterface;
use oat\tao\model\service\InjectionAwareService;
use oat\tao\model\session\Business\Contract\SessionCookieAttributesFactoryInterface;
use oat\tao\model\session\Business\Domain\SessionCookieAttribute;
use oat\tao\model\session\Business\Domain\SessionCookieAttributeCollection;
use oat\taoLti\models\classes\LtiLaunchData;

class SessionCookieAttributesFactory extends InjectionAwareService implements SessionCookieAttributesFactoryInterface
{
    public const SERVICE_ID = 'taoLti/SessionCookieAttributesFactory';

    /** @var SessionCookieAttributesFactoryInterface */
    private $sessionCookieAttributesFactory;
    /** @var SecuritySettingsRepositoryInterface */
    private $securitySettingsRepository;

    public function __construct(
        SessionCookieAttributesFactoryInterface $sessionCookieAttributesFactory,
        SecuritySettingsRepositoryInterface $securitySettingsRepository
    ) {
        parent::__construct();

        $this->sessionCookieAttributesFactory = $sessionCookieAttributesFactory;
        $this->securitySettingsRepository     = $securitySettingsRepository;
    }

    public function create(): SessionCookieAttributeCollection
    {
        $attributes = $this->sessionCookieAttributesFactory->create();

        $ltiLaunchData = LtiLaunchData::fromRequest(Request::currentRequest());

        if (!$ltiLaunchData->hasVariable(LtiLaunchData::LTI_VERSION)) {
            return $attributes;
        }

        $whitelistedSources = $this->securitySettingsRepository->findAll()->findContentSecurityPolicy()->getValue();

        if (!in_array($whitelistedSources, ['*', 'list'], true)) {
            return $attributes;
        }

        return $attributes
            ->add(new SessionCookieAttribute('samesite', 'none'));
    }
}
