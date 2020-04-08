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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model;

use core_kernel_classes_Container;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiLaunchData;
use RuntimeException;

/**
 * @package oat\ltiDeliveryProvider\model
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class LtiLaunchDataService extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/LtiLaunchData';

    /**
     * @param LtiLaunchData $launchData
     * @return core_kernel_classes_Resource
     */
    public function findDeliveryFromLaunchData(LtiLaunchData $launchData)
    {
        return $this->findResource($launchData->getCustomParameter('delivery'));
    }

    /**
     * @param LtiLaunchData $launchData
     *
     * @return core_kernel_classes_Container|core_kernel_classes_Resource
     */
    public function findDeliveryExecutionFromLaunchData(LtiLaunchData $launchData)
    {
        return $this->findResource($launchData->getCustomParameter('execution'));
    }

    /**
     * @param $uri
     *
     * @return core_kernel_classes_Container|core_kernel_classes_Resource
     */
    private function findResource($uri)
    {
        return $this->getServiceLocator()->get(LtiDeliveryFactory::SERVICE_ID)->create($uri);
    }
}
