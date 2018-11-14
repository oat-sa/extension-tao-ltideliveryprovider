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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\AssignmentAuthorizationInterface;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\oatbox\user\User;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoLti\models\classes\TaoLtiSession;

/**
 * Class LtiAssignment
 * @package oat\ltiDeliveryProvider\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiAssignmentAuthorizationService extends ConfigurableService implements AssignmentAuthorizationInterface
{
    const LTI_MAX_ATTEMPTS_VARIABLE = 'custom_max_attempts';
    const SERVICE_ID = 'ltiDeliveryProvider/LtiAssignmentAuthorization';

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
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     * @throws LtiException
     * @throws \common_exception_Error
     * @throws \core_kernel_persistence_Exception
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    protected function verifyToken(\core_kernel_classes_Resource $delivery, User $user)
    {
        $propMaxExec = $delivery->getOnePropertyValue(new \core_kernel_classes_Property(DeliveryContainerService::PROPERTY_MAX_EXEC));
        $maxExec = is_null($propMaxExec) ? 0 : $propMaxExec->literal;

        $currentSession = \common_session_SessionManager::getSession();

        if ($currentSession instanceof TaoLtiSession) {
            $launchData = $currentSession->getLaunchData();
            if ($launchData->hasVariable(self::LTI_MAX_ATTEMPTS_VARIABLE)) {
                $val = $launchData->getVariable(self::LTI_MAX_ATTEMPTS_VARIABLE);
                if (!is_numeric($val)) {
                    throw new LtiException(
                        __('"max_attempts" variable must me numeric.'),
                        LtiErrorMessage::ERROR_INVALID_PARAMETER
                    );
                }
                $maxExec = (integer) $val;
            }
        }

        //check Tokens
        $usedTokens = count($this->getServiceLocator()->get(AttemptServiceInterface::SERVICE_ID)
            ->getAttempts($delivery->getUri(), $user));

        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). " without tokens");
            throw new LtiException(
                __('Attempts limit has been reached.'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }
        return true;
    }
}