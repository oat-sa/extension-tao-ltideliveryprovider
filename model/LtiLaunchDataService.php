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

use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiLaunchData;

/**
 * Class LtiLaunchDataService
 * @package oat\ltiDeliveryProvider\model
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class LtiLaunchDataService extends ConfigurableService
{

    const SERVICE_ID = 'ltiDeliveryProvider/LtiLaunchData';

    /**
     * @param LtiLaunchData $launchData
     * @return \core_kernel_classes_Resource
     * @throws \common_exception_Error
     */
    public function findDeliveryFromLaunchData(LtiLaunchData $launchData)
    {
        $deliveryUri = $launchData->getCustomParameter('delivery');

        if (!is_null($deliveryUri)) {

            $delivery = new \core_kernel_classes_Resource($deliveryUri);
        } else {
            // stored in link
            $delivery = LTIDeliveryTool::singleton()->getDeliveryFromLink();
        }

        return $delivery;
    }
}
