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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\ltiDeliveryProvider\model;

use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\oatbox\user\User;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;

/**
 * Class LtiAssignment
 * @package oat\ltiDeliveryProvider\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiAssignment extends GroupAssignment implements AssignmentService
{
    const LTI_MAX_ATTEMPTS_VARIABLE = 'custom_max_attempts';
    const LTI_SERVICE_ID = 'ltiDeliveryProvider/assignment';

    /**
     * @param string $deliveryIdentifier
     * @param User $user
     * @return bool
     */
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryIdentifier);
        return $this->verifyToken($delivery, $user);
    }

    /**
     * Check Max. number of executions
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     * @throws \taoLti_models_classes_LtiException
     */
    protected function verifyToken(\core_kernel_classes_Resource $delivery, User $user)
    {
        $propMaxExec = $delivery->getOnePropertyValue(new \core_kernel_classes_Property(TAO_DELIVERY_MAXEXEC_PROP));
        $maxExec = is_null($propMaxExec) ? 0 : $propMaxExec->literal;

        $currentSession = \common_session_SessionManager::getSession();

        if ($currentSession instanceof \taoLti_models_classes_TaoLtiSession) {
            $launchData = $currentSession->getLaunchData();
            if ($launchData->hasVariable(self::LTI_MAX_ATTEMPTS_VARIABLE)) {
                $val = $launchData->getVariable(self::LTI_MAX_ATTEMPTS_VARIABLE);
                if (!is_numeric($val)) {
                    throw new \taoLti_models_classes_LtiException(
                        __('"max_attempts" variable must me numeric.'),
                        LtiErrorMessage::ERROR_INVALID_PARAMETER
                    );
                }
                $maxExec = (integer) $val;
            }
        }

        //check Tokens
        /** @var LtiDeliveryExecutionService $executionService */
        $executionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);

        $usedTokens = count($executionService->getLinkedDeliveryExecutions($delivery, $currentSession->getLtiLinkResource(), $user->getIdentifier()));

        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). " without tokens");
            throw new \taoLti_models_classes_LtiException(
                __('Attempts limit has been reached.'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }
        return true;
    }
}