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

namespace oat\ltiDeliveryProvider\model;

use oat\ltiDeliveryProvider\model\execution\LTIDeliveryExecutionLink;
use oat\taoDelivery\model\execution\DeliveryExecution;
use \taoLti_models_classes_LtiTool;
use \taoLti_models_classes_LtiService;
use \core_kernel_classes_Property;
use \core_kernel_classes_Resource;
use \core_kernel_classes_Class;
use oat\oatbox\user\User;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;

class LTIDeliveryTool extends taoLti_models_classes_LtiTool {

	const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIToolDelivery';
	
    const EXTENSION = 'ltiDeliveryProvider';
	const MODULE = 'DeliveryTool';
	const ACTION = 'launch';
    const PROPERTY_LINK_DELIVERY = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LinkDelivery';


	public function getLaunchUrl($parameters = array()) {
		$fullAction = self::ACTION.'/'.base64_encode(json_encode($parameters));
		return _url($fullAction, self::MODULE, self::EXTENSION);
	}
	
	public function getDeliveryFromLink() {
		$remoteLink = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
		return $remoteLink->getOnePropertyValue(new core_kernel_classes_Property(static::PROPERTY_LINK_DELIVERY));
	}
	
	public function linkDeliveryExecution(core_kernel_classes_Resource $link, $userUri, core_kernel_classes_Resource $deliveryExecution) {

	    $link = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID)->createDeliveryExecutionLink($userUri, $link->getUri(), $deliveryExecution->getUri());

	    return !is_null($link );
	}

	public function getFinishUrl(LtiMessage $ltiMessage, $deliveryExecution = null)
    {
        $session = \common_session_SessionManager::getSession();
        /** @var \taoLti_models_classes_LtiLaunchData $launchData */
        $launchData = $session->getLaunchData();
        if ($launchData->hasVariable(DeliveryTool::PARAM_SKIP_THANKYOU) && $launchData->getVariable(DeliveryTool::PARAM_SKIP_THANKYOU) == 'true'
            && $launchData->hasReturnUrl()) {
            $redirectUrl = $launchData->getReturnUrl();
        } else {
            $redirectUrl = _url('thankYou', 'DeliveryRunner', 'ltiDeliveryProvider');
        }

        if ($deliveryExecution !== null) {
            $urlParts = parse_url($redirectUrl);
            if (!isset($urlParts['query'])) {
                $urlParts['query'] = '';
            }
            parse_str($urlParts['query'], $params);
            $params = array_merge($params, $ltiMessage->getUrlParams());
            $urlParts['query'] = http_build_query($params);
            $redirectUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'] . '?' . $urlParts['query'];
        }
        return $redirectUrl;
    }

	/**
	 * Start a new delivery execution
	 * 
	 * @param core_kernel_classes_Resource $delivery
	 * @param core_kernel_classes_Resource $link
	 * @param User $user
	 * @return DeliveryExecution
     * @throws \common_exception_Unauthorized
	 */
	public function startDelivery(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $link, User $user) {
        $assignmentService = $this->getServiceLocator()->get(LtiAssignment::LTI_SERVICE_ID);
        if (!$assignmentService->isDeliveryExecutionAllowed($delivery->getUri(), $user) ) {
            throw new \common_exception_Unauthorized(__('User is not authorized to run this delivery'));
        }
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $deliveryExecution = $stateService->createDeliveryExecution($delivery->getUri(), $user, $delivery->getLabel());

        $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID)->createDeliveryExecutionLink($user->getIdentifier(), $link->getUri(), $deliveryExecution->getIdentifier());

	    return $deliveryExecution;
	}
	
	/**
	 * Returns an array of DeliveryExecution
	 * 
	 * @param core_kernel_classes_Resource $delivery
	 * @param core_kernel_classes_Resource $link
	 * @param string $userId
	 * @return array
	 */
	public function getLinkedDeliveryExecutions(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $link, $userId) {
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
	    $deliveryExecutionService = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);
        $executions = $deliveryExecutionService->getLinkedDeliveryExecutions($delivery, $link, $userId);
        return $executions;
    }

}
