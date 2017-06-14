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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\ltiDeliveryProvider\controller;

use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\StateServiceInterface;
use \taoLti_actions_ToolModule;
use \tao_models_classes_accessControl_AclProxy;
use \tao_helpers_Uri;
use \common_session_SessionManager;
use \common_Logger;
use \core_kernel_classes_Resource;
use \taoLti_models_classes_LtiService;
use oat\taoLti\models\classes\LtiRoles;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\LtiAssignment;

/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 */
class DeliveryTool extends taoLti_actions_ToolModule
{
    /**
     * Setting this parameter to 'true' will prevent resuming a testsession in progress
     * and will start a new testsession whenever the lti tool is launched 
     * 
     * @var string
     */
    const PARAM_FORCE_RESTART = 'custom_force_restart';
    
    /**
     * Setting this parameter to 'true' will prevent the thank you screen to be shown after
     * the test and skip directly to the return url
     *
     * @var string
     */
    const PARAM_SKIP_THANKYOU = 'custom_skip_thankyou';

    /**
     * Setting this parameter to a string will show this string as the title of the thankyou
     * page. (no effect if PARAM_SKIP_THANKYOU is set to 'true')
     *
     * @var string
     */
    const PARAM_THANKYOU_MESSAGE = 'custom_message';
    
    /**
     * (non-PHPdoc)
     * @see taoLti_actions_ToolModule::run()
     */
    public function run()
    {
        $compiledDelivery = $this->getDelivery();
        if (is_null($compiledDelivery) || !$compiledDelivery->exists()) {
            if (tao_models_classes_accessControl_AclProxy::hasAccess('configureDelivery', 'LinkConfiguration','ltiDeliveryProvider')) {
                // user authorised to select the Delivery
                $this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
            } else {
                // user NOT authorised to select the Delivery
                throw new \taoLti_models_classes_LtiException(
                    __('This tool has not yet been configured, please contact your instructor'),
                    LtiErrorMessage::ERROR_INVALID_PARAMETER);
            }
        } else {
            $user = common_session_SessionManager::getSession()->getUser();
            $isLearner = !is_null($user) && in_array(LtiRoles::CONTEXT_LEARNER, $user->getRoles());
            if ($isLearner) {
                if (tao_models_classes_accessControl_AclProxy::hasAccess('runDeliveryExecution', 'DeliveryRunner', 'ltiDeliveryProvider')) {
                    $activeExecution = $this->getActiveDeliveryExecution($compiledDelivery);
                    if ($activeExecution && $activeExecution->getState()->getUri() != DeliveryExecution::STATE_PAUSED) {
                        $deliveryExecutionStateService = $this->getServiceManager()->get(StateServiceInterface::SERVICE_ID);
                        $deliveryExecutionStateService->pause($activeExecution);
                    }
                    $this->redirect($this->getLearnerUrl($compiledDelivery));
                } else {
                    common_Logger::e('Lti learner has no access to delivery runner');
                    $this->returnError(__('Access to this functionality is restricted'), false);
                }   
            } elseif (tao_models_classes_accessControl_AclProxy::hasAccess('configureDelivery', 'LinkConfiguration', 'ltiDeliveryProvider')) {
                $this->redirect(_url('showDelivery', 'LinkConfiguration', null, array('uri' => $compiledDelivery->getUri())));
            } else {
                $this->returnError(__('Access to this functionality is restricted to students'), false);
            }
        }
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return string
     * @throws \taoLti_models_classes_LtiException
     */
    protected function getLearnerUrl(\core_kernel_classes_Resource $delivery)
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        $active = $this->getActiveDeliveryExecution($delivery);
        if ($active !== null) {
            return _url('runDeliveryExecution', 'DeliveryRunner', null, array('deliveryExecution' => $active->getIdentifier()));
        }

        $assignmentService = $this->getServiceManager()->get(LtiAssignment::LTI_SERVICE_ID);
        if ($assignmentService->isDeliveryExecutionAllowed($delivery->getUri(), $user)) {
            return _url('ltiOverview', 'DeliveryRunner', null, array('delivery' => $delivery->getUri()));
        } else {
            throw new \taoLti_models_classes_LtiException(
                __('User is not authorized to run this delivery'),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
        }
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return mixed|null|\taoDelivery_models_classes_execution_DeliveryExecution
     */
    protected function getActiveDeliveryExecution(\core_kernel_classes_Resource $delivery)
    {
        $remoteLink = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();

        $launchData = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        if ($launchData->hasVariable(self::PARAM_FORCE_RESTART) && $launchData->getVariable(self::PARAM_FORCE_RESTART) == 'true') {
            // ignore existing executions to force restart
            $executions = array();
        } else {
            $executions = $this->getTool()->getLinkedDeliveryExecutions($delivery, $remoteLink, $user->getIdentifier());
        }

        $active = null;

        if (empty($executions)) {
            $active = $this->getTool()->startDelivery($delivery, $remoteLink, $user);
        } else {
            $deliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
            foreach ($executions as $deliveryExecution) {
                if (!$deliveryExecutionService->isFinished($deliveryExecution)) {
                    $active = $deliveryExecution;
                    break;
                }
            }
        }
        return $active;
    }
    
    /**
     * (non-PHPdoc)
     * @see taoLti_actions_ToolModule::getTool()
     */
    protected function getTool()
    {
        return LTIDeliveryTool::singleton();
    }
    
    /**
     * Returns the delivery associated with the current link
     * either from url or from the remote_link if configured
     * returns null if none found
     *
     * @return core_kernel_classes_Resource
     */
    private function getDelivery()
    {
        $returnValue = null;
        //passed as aprameter
        if ($this->hasRequestParameter('delivery')) {
            $returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
        } else {
            
            $launchData = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
            $deliveryUri = $launchData->getCustomParameter('delivery');
            
            if (!is_null($deliveryUri)) {
                $returnValue = new core_kernel_classes_Resource($deliveryUri);
            } else {
                // stored in link
                $returnValue = LTIDeliveryTool::singleton()->getDeliveryFromLink();
            }
        }
        return $returnValue;
    }
    
}
