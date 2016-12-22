<?php
/**
 * Created by PhpStorm.
 * User: AlehHutnikau
 * Date: 22-Dec-16
 * Time: 15:28
 */

namespace oat\ltiDeliveryProvider\model;

use oat\taoDelivery\model\AssignmentService;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\oatbox\user\User;

/**
 * Class LtiAssignment
 * @package oat\ltiDeliveryProvider\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiAssignment extends GroupAssignment implements AssignmentService
{
    const LTI_MAX_ATTEMPTS_VARIABLE = 'custom_max_attempts';

    /**
     * @param string $deliveryIdentifier
     * @param User $user
     * @return bool
     */
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryIdentifier);
        return $this->verifyTime($delivery) &&
               $this->verifyToken($delivery, $user);
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
                    throw new \taoLti_models_classes_LtiException(__('"max_attempts" variable must me numeric.'));
                }
                $maxExec = (integer) $val;
            }
        }

        //check Tokens
        $usedTokens = count(\taoDelivery_models_classes_execution_ServiceProxy::singleton()->getUserExecutions($delivery, $user->getIdentifier()));

        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). "without tokens");
            return false;
        }
        return true;
    }
}