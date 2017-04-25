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
 *
 *
 */

namespace oat\ltiDeliveryProvider\controller;

use oat\tao\model\theme\ThemeService;
use oat\taoDelivery\controller\DeliveryServer;
use oat\taoLti\models\classes\theme\LtiHeadless;
use \taoLti_models_classes_LtiService;
use \taoLti_models_classes_LtiLaunchData;
use oat\ltiDeliveryProvider\helper\ResultServer;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoLti\actions\traits\LtiModuleTrait;
use oat\taoLti\models\classes\LtiMessages\LtiErrorMessage;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;

/**
 * Called by the DeliveryTool to override DeliveryServer settings
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 */
class DeliveryRunner extends DeliveryServer
{
    use LtiModuleTrait;

    /**
     * Defines if the top and bottom action menu should be displayed or not
     *
     * @return boolean
     */
    protected function showControls() {
        $themeService = $this->getServiceManager()->get(ThemeService::SERVICE_ID);
        if ($themeService instanceof LtiHeadless) {
            return !$themeService->isHeadless(); 
        }
        return false;
    }
    
    protected function getReturnUrl() {
        $deliveryExecution = $this->getCurrentDeliveryExecution();
        return _url('finishDeliveryExecution', 'DeliveryRunner', 'ltiDeliveryProvider',
            ['deliveryExecution' => $deliveryExecution->getIdentifier()]
        );
    }

    /**
     * Shown uppon returning to a finished delivery execution
     */
    public function ltiOverview() {
        $this->setData('delivery', $this->getRequestParameter('delivery'));
        $this->setData('allowRepeat', true);
        $this->setView('learner/overview.tpl');
    }
    
    public function repeat() {
        $delivery = new \core_kernel_classes_Resource($this->getRequestParameter('delivery'));

        $remoteLink = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();

        try {
            $newExecution = LTIDeliveryTool::singleton()->startDelivery($delivery, $remoteLink, $user);
            $this->redirect(_url('runDeliveryExecution', null, null, array('deliveryExecution' => $newExecution->getIdentifier())));
        } catch (\common_exception_Unauthorized $e) {
            $ltiException = new \taoLti_models_classes_LtiException(
                $e->getMessage(),
                LtiErrorMessage::ERROR_LAUNCH_FORBIDDEN
            );
            $this->returnLtiError($ltiException);
        }
    }
    
    public function thankYou() {
        $launchData = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        
        if ($launchData->hasVariable(taoLti_models_classes_LtiLaunchData::TOOL_CONSUMER_INSTANCE_NAME)) {
            $this->setData('consumerLabel', $launchData->getVariable(taoLti_models_classes_LtiLaunchData::TOOL_CONSUMER_INSTANCE_NAME));
        } elseif($launchData->hasVariable(taoLti_models_classes_LtiLaunchData::TOOL_CONSUMER_INSTANCE_DESCRIPTION)) {
            $this->setData('consumerLabel', $launchData->getVariable(taoLti_models_classes_LtiLaunchData::TOOL_CONSUMER_INSTANCE_DESCRIPTION));
        }
        
        if ($launchData->hasVariable(taoLti_models_classes_LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL)) {
            $this->setData('returnUrl', $launchData->getVariable(taoLti_models_classes_LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL));
        }
        
        if ($launchData->hasVariable(DeliveryTool::PARAM_THANKYOU_MESSAGE)) {
            $this->setData('message', $launchData->getVariable(DeliveryTool::PARAM_THANKYOU_MESSAGE));
        }
        
        $this->setData('allowRepeat', false);
        $this->setView('learner/thankYou.tpl');
    }

    /**
     * Redirect user to return URL
     */
    public function finishDeliveryExecution()
    {
        $session = \common_session_SessionManager::getSession();
        $launchData = $session->getLaunchData();
        if ($launchData->hasVariable(DeliveryTool::PARAM_SKIP_THANKYOU) && $launchData->getVariable(DeliveryTool::PARAM_SKIP_THANKYOU) == 'true'
            && $launchData->hasVariable(taoLti_models_classes_LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL)) {
            $redirectUrl = $launchData->getVariable(taoLti_models_classes_LtiLaunchData::LAUNCH_PRESENTATION_RETURN_URL);
        } else {
            $redirectUrl = _url('thankYou', 'DeliveryRunner', 'ltiDeliveryProvider');
        }
        if ($this->hasRequestParameter('deliveryExecution')) {
            $deliveryExecution = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution(
                $this->getRequestParameter('deliveryExecution')
            );
            $urlParts = parse_url($redirectUrl);
            if (!isset($urlParts['query'])) {
                $urlParts['query'] = '';
            }
            parse_str($urlParts['query'], $params);
            $params = array_merge($params, $this->getLtiMessage($deliveryExecution)->getUrlParams());
            $urlParts['query'] = http_build_query($params);
            $redirectUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?' . $urlParts['query'];
        }
        $this->redirect($redirectUrl);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return LtiMessage
     */
    protected function getLtiMessage(DeliveryExecution $deliveryExecution)
    {
        $state = $deliveryExecution->getState()->getLabel();
        return new LtiMessage($state, null);
    }

    protected function initResultServer($compiledDelivery, $executionIdentifier) {
        //The result server from LTI context depend on call parameters rather than static result server definition
        // lis_outcome_service_url This value should not change from one launch to the next and in general,
        //  the TP can expect that there is a one-to-one mapping between the lis_outcome_service_url and a particular oauth_consumer_key.  This value might change if there was a significant re-configuration of the TC system or if the TC moved from one domain to another.
        $launchData = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        ResultServer::initLtiResultServer($compiledDelivery, $executionIdentifier, $launchData);
    }

}
