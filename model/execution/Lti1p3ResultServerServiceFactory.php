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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\execution;

use common_session_SessionManager;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\ResultServerServiceFactoryInterface;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoResultServer\models\classes\NoResultStorage;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoResultServer\models\classes\implementation\ResultServerService as ResultServerServiceImplementation;

class Lti1p3ResultServerServiceFactory extends ConfigurableService implements ResultServerServiceFactoryInterface
{
    public function __construct()
    {
        $serviceManager = ServiceManager::getServiceManager();

        $this->setServiceManager($serviceManager);
    }

    public function create(): ResultServerService
    {
        $session = common_session_SessionManager::getSession();

        $isDryRun = $session && in_array(LtiRoles::CONTEXT_LTI1P3_INSTRUCTOR, $session->getUser()->getRoles());

        if ($isDryRun) {
            $service = new ResultServerServiceImplementation([
                ResultServerServiceImplementation::OPTION_RESULT_STORAGE => NoResultStorage::class
            ]);

            $this->propagate($service);

            return $service;
        }

        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
    }
}
