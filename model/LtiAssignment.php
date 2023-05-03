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
 */

namespace oat\ltiDeliveryProvider\model;

use common_exception_Error;
use core_kernel_classes_Resource as KernelResource;
use core_kernel_persistence_Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\taoDelivery\model\AttemptServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoLti\models\classes\LtiClientException;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoLti\models\classes\LtiVariableMissingException;
use oat\taoLti\models\classes\TaoLtiSession;

/**
 * Class LtiAssignment
 *
 * @package oat\ltiDeliveryProvider\model
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiAssignment extends ConfigurableService
{
    use OntologyAwareTrait;
    use LoggerAwareTrait;

    public const LTI_MAX_ATTEMPTS_VARIABLE = 'custom_max_attempts';

    /**
     * @deprecated Use LtiAssignmentAuthorizationService::SERVICE_ID instead
     */
    public const LTI_SERVICE_ID = 'ltiDeliveryProvider/assignment';

    public const SERVICE_ID = 'ltiDeliveryProvider/assignment';

    /**
     * @param string $deliveryIdentifier
     * @param User $user
     *
     * @return bool
     */
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = $this->getResource($deliveryIdentifier);

        $this->verifyAvailabilityFrame($delivery);

        return $this->verifyToken($delivery, $user);
    }

    /**
     * Check Max. number of executions
     *
     * @param KernelResource $delivery
     * @param User $user
     *
     * @throws LtiException
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     * @throws LtiVariableMissingException
     *
     * @return bool
     */
    protected function verifyToken(KernelResource $delivery, User $user)
    {
        $propMaxExec = $delivery->getOnePropertyValue($this->getProperty(DeliveryContainerService::PROPERTY_MAX_EXEC));
        $maxExec = is_null($propMaxExec) ? 0 : (int) $propMaxExec->literal;

        $currentSession = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();

        if ($currentSession instanceof TaoLtiSession) {
            $launchData = $currentSession->getLaunchData();

            if ($launchData->hasVariable(self::LTI_MAX_ATTEMPTS_VARIABLE)) {
                $val = $launchData->getVariable(self::LTI_MAX_ATTEMPTS_VARIABLE);

                if (!is_numeric($val)) {
                    throw new LtiClientException(
                        __('"max_attempts" variable must me numeric.'),
                        LtiErrorMessage::ERROR_INVALID_PARAMETER
                    );
                }
                $maxExec = (int) $val;
            }
        }

        //check Tokens
        $usedTokens = count($this->getServiceLocator()->get(AttemptServiceInterface::SERVICE_ID)
            ->getAttempts($delivery->getUri(), $user));

        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            $this->logDebug('Attempt to start the compiled delivery ' . $delivery->getUri() . ' without tokens');

            throw new LtiClientException(
                __('Attempts limit has been reached.'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }

        return true;
    }

    private function verifyAvailabilityFrame(KernelResource $delivery): void
    {
        @[[$scheduledStartTimeProperty], [$scheduledEndTimeProperty]] = array_values($delivery->getPropertiesValues(
            [
                $this->getProperty(DeliveryAssemblyService::PROPERTY_START),
                $this->getProperty(DeliveryAssemblyService::PROPERTY_END),
            ]
        ));

        $scheduledStartTime = (int)(string)$scheduledStartTimeProperty ?: 0;
        $scheduledEndTime = (int)(string)$scheduledEndTimeProperty ?: PHP_INT_MAX;

        $currentTime = time();

        if ($scheduledStartTime > $currentTime || $scheduledEndTime <= $currentTime) {
            $this->logDebug("Attempt to start the compiled delivery {$delivery->getUri()} at unscheduled time");

            throw new LtiClientException(
                __('The delivery is currently unavailable.'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }
    }
}
