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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model\results;

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoResultServer\models\classes\search\ResultsWatcher;

/**
 * Class LtiResultWatcher
 * @package oat\ltiDeliveryProvider\model\tasks
 */
class LtiResultWatcher extends ResultsWatcher
{
    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return array
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    protected function getCustomData(DeliveryExecutionInterface $deliveryExecution)
    {
        $data = parent::getCustomData($deliveryExecution);
        $session = \common_session_SessionManager::getSession();
        if ($session instanceof \taoLti_models_classes_TaoLtiSession) {
            $lunchData = $session->getLaunchData();
            if ($lunchData->hasVariable(\taoLti_models_classes_LtiLaunchData::RESOURCE_LINK_ID)) {
                $data[\taoLti_models_classes_LtiLaunchData::RESOURCE_LINK_ID] = $lunchData->getVariable(\taoLti_models_classes_LtiLaunchData::RESOURCE_LINK_ID);
            }
        }

        return $data;
    }

}
