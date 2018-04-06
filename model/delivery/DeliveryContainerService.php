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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */

namespace oat\ltiDeliveryProvider\model\delivery;

use oat\taoDeliveryRdf\model\DeliveryContainerService as DeliveryRdfContainerService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoLti\models\classes\TaoLtiSession;

/**
 * Override the DeliveryContainerService in order to filter the plugin list based on the security flag.
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class DeliveryContainerService extends DeliveryRdfContainerService
{
    const CUSTOM_LTI_SECURE = 'custom_secure';

    /**
     * Validate and prepare launch variables
     * @param LtiLaunchData $launchData
     * @throws LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    protected function validateLtiParams(LtiLaunchData $launchData)
    {
        if ($launchData->hasVariable(self::CUSTOM_LTI_SECURE)) {
            $val = mb_strtolower($launchData->getVariable(self::CUSTOM_LTI_SECURE));
            if ($val !== 'true' && $val !== 'false') {
                throw new LtiException(
                    __('Wrong value of "secure" variable.'),
                    LtiErrorMessage::ERROR_INVALID_PARAMETER
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function getActiveFeatures(\core_kernel_classes_Resource $delivery)
    {
        $result = parent::getActiveFeatures($delivery);
        $currentSession = \common_session_SessionManager::getSession();
        if ($currentSession instanceof TaoLtiSession) {
            $launchData = $currentSession->getLaunchData();
            $this->validateLtiParams($launchData);
            if ($launchData->hasVariable(self::CUSTOM_LTI_SECURE)) {
                if ($launchData->getVariable(self::CUSTOM_LTI_SECURE) === 'true') {
                    $result[] = 'security';
                } else {
                    $result = array_diff($result, ['security']);
                }
            }
        }
        return array_unique($result);
    }
}
