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

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\execution\DeliveryExecutionManagerService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoQtiTest\models\TestSessionService;
use qtism\data\AssessmentTest;
use qtism\data\TestPart;
use \taoLti_models_classes_LtiTool;
use \taoLti_models_classes_LtiService;
use \core_kernel_classes_Property;
use \core_kernel_classes_Resource;
use \core_kernel_classes_Class;
use oat\oatbox\user\User;
use \taoDelivery_models_classes_execution_ServiceProxy;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\AssignmentServiceRegistry;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;

class LTIDeliveryTool extends taoLti_models_classes_LtiTool {

	const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIToolDelivery';
    const EXTENDED_TIME  = 'custom_extended_time';
    const EXTENSION = 'ltiDeliveryProvider';
	const MODULE = 'DeliveryTool';
	const ACTION = 'launch';
	
	public function getLaunchUrl($parameters = array()) {
		$fullAction = self::ACTION.'/'.base64_encode(json_encode($parameters));
		return _url($fullAction, self::MODULE, self::EXTENSION);
	}
	
	public function getDeliveryFromLink() {
		$remoteLink = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
		return $remoteLink->getOnePropertyValue(new core_kernel_classes_Property(PROPERTY_LINK_DELIVERY));
	}
	
	public function linkDeliveryExecution(core_kernel_classes_Resource $link, $userUri, core_kernel_classes_Resource $deliveryExecution) {
	    
	    $class = new core_kernel_classes_Class(CLASS_LTI_DELIVERYEXECUTION_LINK);
	    $link = $class->createInstanceWithProperties(array(
	        PROPERTY_LTI_DEL_EXEC_LINK_USER => $userUri,
	        PROPERTY_LTI_DEL_EXEC_LINK_LINK => $link,
            PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID => $deliveryExecution
	    ));
	    return $link instanceof core_kernel_classes_Resource;
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
	 * @return \taoDelivery_models_classes_execution_DeliveryExecution
     * @throws \common_exception_Unauthorized
	 */
	public function startDelivery(core_kernel_classes_Resource $delivery, core_kernel_classes_Resource $link, User $user) {
        $assignmentService = $this->getServiceLocator()->get(LtiAssignment::LTI_SERVICE_ID);
        if (!$assignmentService->isDeliveryExecutionAllowed($delivery->getUri(), $user) ) {
            throw new \common_exception_Unauthorized(__('User is not authorized to run this delivery'));
        }
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $deliveryExecution = $stateService->createDeliveryExecution($delivery->getUri(), $user, $delivery->getLabel());

	    $class = new core_kernel_classes_Class(CLASS_LTI_DELIVERYEXECUTION_LINK);
	    $class->createInstanceWithProperties(array(
	        PROPERTY_LTI_DEL_EXEC_LINK_USER => $user->getIdentifier(),
	        PROPERTY_LTI_DEL_EXEC_LINK_LINK => $link,
	        PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID => $deliveryExecution->getIdentifier()
	    ));
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
        $deliveryExecutionService = $this->getServiceLocator()->get(LtiDeliveryExecutionService::SERVICE_ID);
        return $deliveryExecutionService->getLinkedDeliveryExecutions($delivery, $link, $userId);
    }

    /**
     * @param DeliveryExecution $deliveryExecution
     * @param $extendedTime
     * @return bool
     */
    public function updateDeliveryExtendedTime(DeliveryExecution $deliveryExecution, $extendedTime)
    {
        /** @var DeliveryExecutionManagerService $deliveryExecutionManagerService */
        $deliveryExecutionManagerService = $this->getServiceLocator()->get(DeliveryExecutionManagerService::SERVICE_ID);

        /** @var TestSessionService $testSessionService */
        $testSessionService = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
        $inputParameters = $testSessionService->getRuntimeInputParameters($deliveryExecution);

        /** @var AssessmentTest $testDefinition */
        $testDefinition = \taoQtiTest_helpers_Utils::getTestDefinition($inputParameters['QtiTestCompilation']);
        $deliveryExecutionArray[] = $deliveryExecution;

        $secondDiff = null;
        if ($maxTime = $testDefinition->getTimeLimits()->getMaxTime()) {
            $seconds = $maxTime->getSeconds(true);
            $secondsNew = $seconds * $extendedTime;
            $secondDiff = floor(($secondsNew - $seconds) / 60) * 60;

            $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);
            $data = $deliveryMonitoringService->getData($deliveryExecution);
            $dataArray = $data->get();
            if (!isset($dataArray[DeliveryMonitoringService::REMAINING_TIME])) {
                $data->update(DeliveryMonitoringService::REMAINING_TIME, $seconds);
            }
        }
        $deliveryExecutionManagerService->setExtraTime($deliveryExecutionArray, $secondDiff, $extendedTime);
        return true;
    }
}
