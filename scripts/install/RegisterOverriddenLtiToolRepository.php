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

namespace oat\ltiDeliveryProvider\scripts\install;

use oat\ltiDeliveryProvider\model\options\DataAccess\Mapper\OptionCollectionMapper;
use oat\ltiDeliveryProvider\model\options\DataAccess\Repository\OverriddenLtiToolsRepository;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\session\SessionService;
use oat\taoQtiTest\models\TestCategoryPresetProvider;

class RegisterOverriddenLtiToolRepository extends InstallAction
{
    public function __invoke($params)
    {
        $serviceManager = $this->getServiceManager();

        $serviceManager->register(
            OverriddenLtiToolsRepository::SERVICE_ID,
            new OverriddenLtiToolsRepository(
                $serviceManager->get(TestCategoryPresetProvider::SERVICE_ID),
                $serviceManager->get(SessionService::SERVICE_ID),
                $serviceManager->get(OptionCollectionMapper::SERVICE_ID)
            )
        );
    }
}
