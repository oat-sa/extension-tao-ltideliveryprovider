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

use oat\oatbox\session\SessionService;
use oat\taoDeliveryRdf\model\DeliveryContainerService as DeliveryRdfContainerService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoLti\models\classes\TaoLtiSession;

/**
 * Override the DeliveryContainerService in order to filter the plugin list based on the security flag.
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class DeliveryContainerService extends DeliveryRdfContainerService
{
    const CUSTOM_LTI_SECURE = 'custom_secure';

    const CUSTOM_LTI_TAO_FEATURES = 'custom_x_tao_features';

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
        $currentSession = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();
        if ($currentSession instanceof TaoLtiSession) {
            $launchData = $currentSession->getLaunchData();
            $allFeatures = $this->getAllAvailableFeatures();
            $ltiFeatures = $this->getLtiFeaturesList($launchData);
            $enabledLtiFeatures = [];
            $disabledLtiFeatures = [];
            foreach ($ltiFeatures as $featureName => $featureStatus) {
                if (empty($allFeatures[$featureName])) {
                    continue;
                }

                if (filter_var($featureStatus, FILTER_VALIDATE_BOOLEAN)) {
                    $enabledLtiFeatures[] = $featureName;
                } else {
                    $disabledLtiFeatures[] = $featureName;
                }
            }

            // TODO: Deprecated method, to be removed in version 11.0.0
            $this->validateLtiParams($launchData);
            if (!isset($ltiFeatures['security']) && $launchData->hasVariable(self::CUSTOM_LTI_SECURE)) {
                if ($launchData->getVariable(self::CUSTOM_LTI_SECURE) === 'true') {
                    $enabledLtiFeatures[] = 'security';
                } else {
                    $disabledLtiFeatures[] = 'security';
                }
            }

            $result = array_merge($result, $enabledLtiFeatures);
            $result = array_diff($result, $disabledLtiFeatures);
        }

        return array_unique($result);
    }

    /**
     * @param LtiLaunchData $launchData
     * @return array
     */
    private function getLtiFeaturesList(LtiLaunchData $launchData) {
        $ltiFeatures = [];
        try {
            if (!$launchData->hasVariable(self::CUSTOM_LTI_TAO_FEATURES)) {
                return $ltiFeatures;
            }

            $toolsLtiVariable = $launchData->getVariable(self::CUSTOM_LTI_TAO_FEATURES);
            $features = json_decode($toolsLtiVariable, true);
            if (is_array($features)) {
                $ltiFeatures = $features;
            }
        } catch (LtiVariableMissingException $e) {
            $this->logWarning('Cannot get LTI parameter: ' . self::CUSTOM_LTI_TAO_FEATURES, [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return $ltiFeatures;
    }
}
